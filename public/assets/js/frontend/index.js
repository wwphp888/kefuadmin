define(['jquery', 'bootstrap', 'frontend', 'socket', 'upload'], function ($, undefined, Frontend, Socket, Upload) {
    var Controller = {
        visitor_id: visitor_id,
        name: name,
        avatar: 'http://161.117.235.17/assets/img/avatar.png',
        merchant_id: merchant_id,
        connect_code: '',
        connect_name: '',
        connect_avatar: '',
        port: 9510,

        index: function () {
            var protocol = (window.location.protocol == 'http:') ? 'ws:' : 'wss:';
            Socket.init({
                wsUrl: protocol + '//' + window.location.hostname + ':' + Controller.port,
                HeartBeat: Controller.visitor.heartBeatData(),  //心跳数据
                timeout: 60000,  //60秒发送心跳包
                onopen: function () {
                    console.log('连接成功');
                    //用户登录
                    Controller.visitor.visitorLogin();
                },
                onmessage: function (event) {
                    var obj = Controller.visitor.decode(event.data);
                    return Controller.visitor[obj.method] ? Controller.visitor[obj.method].call(this, obj) : true;
                }, onerror: function () {
                    console.log("服务错误....");
                }, onclose: function () {
                    console.log("断开连接");
                }
            });
            var c = $('.timely-chat-content');
            var f = $('.timely-chat-footer.mobile');

            //发送消息
            $('.chat-send').on("click", function () {
                var content = $('.message').val();
                if (content.length <= 0 || content == '') {
                    return;
                }
                Controller.visitor.sendMessage(content);
                $('.message').val('');
            });

            //监听手机打开图片事件
            $('.chat-tool-add').on('click', function () {
                var c = f.find('.widget-layer-view');
                if (c.is(':hidden')) {
                    c.show();
                    Upload.api.plupload($('#chat-upload-img'), function(up, ret) {
                        if (ret.data.url) {
                            Controller.visitor.sendMessage('[img src=' + ret.data.url + ']');
                            c.hide();
                        }
                    });
                } else {
                    c.hide();
                }
            });
            $(document).on('click',function(e){
                var $target = $(e.target);
                if(!$target.is('.chat-tool-add') && !$target.closest('.widget-layer-view').length){
                    f.find('.widget-layer-view').addClass('layui-hide');
                }
            });

            // 展示大图
            $("body").on('click', '.chat-content img', function () {
                var src = this.src;
                var dom = $('<div style="position:fixed;width:100%;height:100%;z-index: 9999999; background-color: rgba(0, 0, 0, 1); display:flex;justify-content: center;align-items: center"></div>').appendTo('body');
                var img = $('<img src="'+src+'" style="max-height: 100%;max-width: 100%;z-index: 999999999">').appendTo(dom);
                dom.on('click', function(e) {
                    dom.remove();
                    img.remove();
                });
            });

        },
        visitor: {
            //组装发送数据  //定义标准格式
            encode: function (method, data) {
                var msg = {};
                msg.method = method;
                msg.token = '';
                msg.param = data;
                return JSON.stringify(msg);
            },
            //解析回源数据
            decode: function (obj) {
                return JSON.parse(obj);
            },
            //发送的心跳数据
            heartBeatData: function () {
                return this.encode('heartBeat', {str: 'ping'});
            },
            visitorLogin: function () {
                Socket.send(this.encode('visitorLogin', {
                    visitor_id: Controller.visitor_id,
                    visitor_name: Controller.name,
                    visitor_avatar: Controller.avatar,
                    merchant_id: Controller.merchant_id
                }));
            },
            //上线回调
            online: function (o, t) {
                Controller.visitor.connectKf();
                Controller.connect_code = o.data.connect_code;
                Controller.connect_name = o.data.name;
                Controller.connect_avatar = o.data.avatar;
            },
            //连接客服
            connectKf: function () {
                Socket.send(this.encode('connectKf', {
                    visitor_id: Controller.visitor_id,
                    merchant_id: Controller.merchant_id
                }));
            },
            //连接客服返回回调
            connectKefuCallback: function (o) {
                //分配客服或者机器人成功
                if (o.code == 200) {
                    Controller.connect_code = o.data.connect_code;
                    Controller.connect_name = o.data.connect_name;
                    Controller.connect_avatar = o.data.connect_avatar;
                    alert(Controller.connect_name);
                } else {
                    alert(o.msg);
                    return true;
                }
            },
            //发送消息
            sendMessage: function (message) {
                if (!Controller.connect_code) {
                    alert('已断开连接');
                    return;
                }
                Socket.send(this.encode('message', {
                    from_id: Controller.visitor_id,
                    from_name: Controller.name,
                    from_avatar: Controller.avatar,
                    to_id: Controller.connect_code,
                    to_name: Controller.connect_name,
                    to_avatar: Controller.connect_avatar,
                    message: message,
                    merchant_id: Controller.merchant_id
                }));
            },
            //访客发送消息回调
            message: function (o) {
                console.log(o);
                if (o.code == 200) {
                    Controller.visitor.addMeassge(o.data);
                }
            },
            //对方发送消息回调
            chatMessage: function (o) {
                if (o.code == 200) {
                    Controller.visitor.toAddMessage(o.data);
                }
            },
            //结束人工客服
            diffClose: function (o) {
                Controller.connect_code = '';
                Controller.connect_name = '';
                Controller.connect_avatar = '';
            },
            addMeassge: function(h) {
                var html = '<div data-id="' + h.log_id + '" class="chat-item is-right">' +
                    '<p class="mineTop"><span class="mineTime">' + h.create_time + '</span></p>' +
                    '<div class="msg">' +
                    '<div class="avatar">' +
                    '<img src="'+ Controller.avatar + '" alt="">' +
                    '</div>' +
                    '<div class="content-box"  data-type="text"> ' +
                    '<div class="bubble color-custom">' +
                    '<div class="chat-content">' + Controller.visitor.content(h.message) + '</div>' +
                    '<span class="mineTips"></span> ' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
                $('.timely-chat-context').append(html);
                this.scrollBottom();
            },
            toAddMessage: function(h) {
                var html = '<div data-id="' + h.log_id + '" class="chat-item is-left">' +
                    '<p class="mineTop"><span class="mineTime">' + h.create_time + '</span></p> ' +
                    '<div class="msg"> ' +
                    '<div class="avatar">' +
                    '<img src="' + h.from_avatar + '" alt="">' +
                    '</div>' +
                    '<div class="content-box" data-type="text">' +
                    '<div class="bubble">'+
                        '<div class="chat-content">' + Controller.visitor.content(h.message) + '</div>'+
                        '<span class="mineTips"></span>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
                $('.timely-chat-context').append(html);
                this.scrollBottom();
            },
            //内容转义
            content: function (content) {
                //支持的html标签
                var html = function (end) {
                    return new RegExp('\\n*\\[' + (end || '') + '(br|a|code|img|pre|div|button|span|p|table|thead|th|tbody|tr|td|ul|li|ol|li|dl|dt|dd|h2|h3|h4|h5)([\\s\\S]*?)\\]\\n*', 'g');
                };
                content = (content || '').replace(/&(?!#?[a-zA-Z0-9]+;)/g, '&amp;')
                    .replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/'/g, '&#39;').replace(/"/g, '&quot;') //XSS
                    .replace(/@(\S+)(\s+?|$)/g, '@<a href="javascript:;">$1</a>$2') //转义@
                    .replace(/face\[[\u4e00-\u9fa5]*\w*\]/g, function (i) {  //转义表情
                        i = i.replace( /[face\[\]]/g, '');
                        return '<img src="' + $.emoticons._hash[i] +'">';
                    })
                    .replace(/file\([\s\S]+?\)\[[\s\S]*?\]/g, function (str) { //转义文件
                        var href = (str.match(/file\(([\s\S]+?)\)\[/) || [])[1];
                        var text = (str.match(/\)\[([\s\S]*?)\]/) || [])[1];
                        if (!href) return str;
                        return '<a class="layui-timely-file" href="' + href + '" download target="_blank"><i class="layui-icon" style="font-size: 30px;">&#xe61e;</i><cite>' + (text || href) + '</cite></a>';
                    })
                    .replace(html(), '\<$1 $2\>').replace(html('/'), '\</$1\>') //转移HTML代码
                    .replace(/\n/g, '<br>'); //转义换行
                return content;
            },
            scrollBottom: function () {
                var c = $(".timely-chat-context");
                c.scrollTop(c[0].scrollHeight);
                c.children("div:last").find("img").load(function () {
                    c.scrollTop(c[0].scrollHeight);
                });
            },

        }
    };
    return Controller;
});