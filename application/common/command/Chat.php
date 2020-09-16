<?php

namespace app\common\command;

use Swoole\Process;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Config;
use think\facade\Env;
use think\swoole\Http as HttpServer;
use think\Container;
use chat\Table;
use think\swoole\Server as ThinkServer;
use chat\Event;
use think\Db;
use chat\Service;
use chat\TcpService;

/**
 * Swoole 命令行，支持操作：start|stop|restart|reload
 * 支持应用配置目录下的swoole_server.php文件进行参数配置
 */
class Chat extends Command
{
    protected $config = [];

    public function configure()
    {
        $this->setName('chat')
            ->addArgument('action', Argument::OPTIONAL, "start|stop|restart|reload|status", 'start')
            ->addOption('host', 'H', Option::VALUE_OPTIONAL, 'the host of swoole server.', null)
            ->addOption('port', 'p', Option::VALUE_OPTIONAL, 'the port of swoole server.', null)
            ->addOption('daemon', 'd', Option::VALUE_NONE, 'Run the swoole server in daemon mode.')
            ->setDescription('chat Swoole Server');
    }

    public function execute(Input $input, Output $output)
    {
        //检查swoole 版本
        $this->checkEnvironment();
        $action = $input->getArgument('action');
        $logo = <<<EOL
        ----------------------------------------
        |   Swoole:Timely Chat Service         |
        |--------------------------------------|
        |    USAGE: php think chat start       |
        |--------------------------------------|
        |    1. start    以debug模式开启服务   |
        |    2. start -d 以daemon模式开启服务  |
        |    3. status   查看服务状态          |
        |    4. reload   热加载                |
        |    5. stop     关闭服务              |
        ----------------------------------------'
EOL;
        $output->writeln($logo . PHP_EOL);

        if (!in_array($action, ['start', 'stop', 'reload', 'restart', 'status'])) {
            $output->writeln("<error>Invalid argument action:{$action}, Expected start|stop|restart|reload .</error>");
            return false;
        }
        $this->init();
        $this->$action();
    }

    /**
     * swoole服务参数初始化
     */
    protected function init()
    {
        $this->config = Config::pull('timely');

        if (empty($this->config['pid_file'])) {
            $this->config['pid_file'] = Env::get('runtime_path') . 'swoole_server.pid';
        }
        // 避免pid混乱
        $this->config['pid_file'] .= '_' . $this->getPort();
        if (empty($this->config['log_file'])) {
            $this->config['log_file'] = Env::get('runtime_path') . 'swoole_server.log';
        }

        $swoole_service_config = Config::pull('swoole_server');
        $task_config = Config::pull('task');

        $this->config['app_path'] = isset($swoole_service_config['app_path']) ? $swoole_service_config['app_path'] : '/data/timely_service_seller/application/';
        $this->config['daemonize'] = isset($swoole_service_config['daemonize']) ? $swoole_service_config['daemonize'] : false;
        $this->config['worker_num'] = isset($swoole_service_config['worker_num']) ? $swoole_service_config['worker_num'] : 1;//主进程数量
        $this->config['reload_async'] = isset($swoole_service_config['reload_async']) ? $swoole_service_config['reload_async'] : true;;
        $this->config['task_worker_num'] = isset($task_config['task_work_num']) ? $task_config['task_work_num'] : 20;//task进程数量

    }

    /**
     * 检查环境
     */
    protected function checkEnvironment()
    {
        if (!version_compare(SWOOLE_VERSION, '4.3.1', 'ge')) {
            $this->output->writeln('Your Swoole version must be higher than `4.3.1`.');
            exit(1);
        }
    }

    /**
     * 启动server
     * @access protected
     * @return void
     */
    protected function start()
    {
        $pid = $this->getMasterPid();

        if ($this->isRunning($pid)) {
            $this->output->writeln('<error>swoole server process is already running.</error>');
            return false;
        }

        $this->output->writeln('Starting swoole server...');

        $host = $this->getHost();
        $port = $this->getPort();
        if ($this->config['open_ssl']['open_status'] == 'on') {
            $this->config['ssl_cert_file'] = $this->config['open_ssl']['ssl']['local_cert'];
            $this->config['ssl_key_file'] = $this->config['open_ssl']['ssl']['local_pk'];
            $swoole = new \Swoole\Websocket\Server($host, $port, SWOOLE_PROCESS, SWOOLE_SOCK_TCP | SWOOLE_SSL);
        } else {
            $swoole = new \Swoole\Websocket\Server($host, $port, SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
        }

        // 开启守护进程模式
        if ($this->input->hasOption('daemon')) {
            $this->config['daemonize'] = true;
        }

        foreach ($this->config as $name => $val) {
            if (0 === strpos($name, 'on')) {

                $swoole->on(substr($name, 2), $val);
                unset($this->config[$name]);
            }
        }
        $swoole->on('onWorkerStart', [new Service(), 'onWorkerStart']);
        $swoole->on('onWorkerStop', [new Service(), 'onWorkerStop']);
        $swoole->on('onWorkerExit', [new Service(), 'onWorkerExit']);
        $swoole->on('onOpen', [new Service(), 'onOpen']);
        $swoole->on('onMessage', [new Service(), 'onMessage']);
        $swoole->on('onClose', [new Service(), 'onClose']);
        $swoole->on('onTask', [new Service(), 'onTask']);
        $swoole->on('onFinish', [new Service(), 'onFinish']);
        //后台推送消息用http方式
        $swoole->on('request', function($request, $response) use ($swoole) {
            try {
                call_user_func_array([new Service(), 'onRequest'], [$request, $response, $swoole]);
                $response->end("success");
            } catch (\Exception $e) {
                $response->end($e->getMessage());
            }

        });
        // 设置swoole服务器参数
        $swoole->set($this->config);


        //设置swoole table
        $table = new Table();
        $swoole->table = $table;

        $this->output->writeln("Swoole Websocket server started: <{$host}:{$port}>" . PHP_EOL);
        $this->output->writeln('You can exit with <info>`CTRL-C`</info>');

        // 启动服务
        $swoole->start();
    }

    /**
     * 柔性重启server
     * @access protected
     * @return void
     */
    protected function reload()
    {
        // 柔性重启使用管理PID
        $pid = $this->getMasterPid();

        if (!$this->isRunning($pid)) {
            $this->output->writeln('<error>no swoole server process running.</error>');
            return false;
        }
        $this->output->writeln('Reloading swoole server...');
        Process::kill($pid, SIGUSR1);
        $this->output->writeln('> success');
    }

    /**
     * 停止server
     * @access protected
     * @return void
     */
    protected function stop()
    {
        $pid = $this->getMasterPid();

        if (!$this->isRunning($pid)) {
            $this->output->writeln('<error>no swoole server process running.</error>');
            return false;
        }

        $this->output->writeln('Stopping swoole server...');
        Process::kill($pid, SIGTERM);
        $this->removePid();
        //处理业务数据  以保持业务完整性
        Db::name('kefu_info')->where('kf_status', 1)->update(['online_status' => 2, 'client_id' => 0, 'update_time' => date('Y-m-d H:i:s')]);
        Db::name('visitor')->where('online_status', 1)->update(['online_status' => 2, 'client_id' => 0, 'update_time' => date('Y-m-d H:i:s')]);
        Db::name('visitor_service_log')->where('connect_stauts', 1)->update(['connect_stauts' => 2, 'end_time' => date('Y-m-d H:i:s')]);
        //移除所有当前会话数据 并往历史记录插入信息
        $list = Db::name('visitor_queue')->field('visitor_id,visitor_name,visitor_avatar,visitor_ip,address,source,mc_code,now() as create_time,kf_code')->select();
        if ($list) {
            Db::name('service_record')->data($list)->limit(1000)->insertAll();
            Db::name('visitor_queue')->delete(true);
        }
        //清除redis 缓存信息
        //  sleep(3);
        $this->output->writeln('> success');
    }

    protected function getHost()
    {
        if ($this->input->hasOption('host')) {
            $host = $this->input->getOption('host');
        } else {
            if (!empty($this->config['host'])) {
                $host = $this->config['host'];
            } else {
                $this->config['host'] = '0.0.0.0';
                $host = $this->config['host'];
            }

        }
        return $host;
    }

    /**
     * 删除PID文件
     * @access protected
     * @return void
     */
    protected function removePid()
    {
        $masterPid = $this->config['pid_file'];
        if (is_file($masterPid)) {
            unlink($masterPid);
        }
    }

    protected function getPort()
    {
        if ($this->input->hasOption('port')) {
            $port = $this->input->getOption('port');
        } else {
            $port = !empty($this->config['socket_port']) ? $this->config['socket_port'] : 9501;
        }
        return $port;
    }

    /**
     * 获取主进程PID
     * @access protected
     * @return int
     */
    protected function getMasterPid()
    {
        $pidFile = $this->config['pid_file'];

        if (is_file($pidFile)) {
            $masterPid = (int)file_get_contents($pidFile);
        } else {
            $masterPid = 0;
        }

        return $masterPid;
    }

    /**
     * 判断PID是否在运行
     * @access protected
     * @param  int $pid
     * @return bool
     */
    protected function isRunning($pid)
    {
        if (empty($pid)) {
            return false;
        }

        return Process::kill($pid, 0);
    }

    protected function status()
    {

        echo str_pad("-", 90, '-', STR_PAD_BOTH) . PHP_EOL;
        echo "|" . str_pad("启动/关闭", 92, ' ', STR_PAD_BOTH) . "|" . PHP_EOL;
        echo str_pad("-", 90, '-', STR_PAD_BOTH) . PHP_EOL;

        echo str_pad("Start success.", 50, ' ', STR_PAD_BOTH) .
            str_pad("php think chat stop", 50, ' ', STR_PAD_BOTH) . PHP_EOL;

        echo str_pad("-", 90, '-', STR_PAD_BOTH) . PHP_EOL;
        echo "|" . str_pad("版本信息", 92, ' ', STR_PAD_BOTH) . "|" . PHP_EOL;
        echo str_pad("-", 90, '-', STR_PAD_BOTH) . PHP_EOL;

        echo str_pad("Swoole Version:" . SWOOLE_VERSION, 50, ' ', STR_PAD_BOTH) .
            str_pad("PHP Version:" . PHP_VERSION, 50, ' ', STR_PAD_BOTH) . PHP_EOL;

        echo str_pad("-", 90, '-', STR_PAD_BOTH) . PHP_EOL;
        echo "|" . str_pad("IP 信息", 90, ' ', STR_PAD_BOTH) . "|" . PHP_EOL;
        echo str_pad("-", 90, '-', STR_PAD_BOTH) . PHP_EOL;

        echo str_pad("IP:" . '0.0.0.0', 50, ' ', STR_PAD_BOTH) .
            str_pad("PORT:" . $this->config['socket_port'], 50, ' ', STR_PAD_BOTH) . PHP_EOL;

        echo str_pad("-", 90, '-', STR_PAD_BOTH) . PHP_EOL;
        echo "|" . str_pad("进程信息", 92, ' ', STR_PAD_BOTH) . "|" . PHP_EOL;
        echo str_pad("-", 90, '-', STR_PAD_BOTH) . PHP_EOL;

        echo str_pad("Swoole进程", 20, ' ', STR_PAD_BOTH) .
            str_pad('进程别名', 30, ' ', STR_PAD_BOTH) .
            str_pad('进程ID', 18, ' ', STR_PAD_BOTH) .
            str_pad('父进程ID', 18, ' ', STR_PAD_BOTH) .
            str_pad('用户', 18, ' ', STR_PAD_BOTH) . PHP_EOL;
    }
}
