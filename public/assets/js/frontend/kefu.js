define(['jquery', 'bootstrap', 'frontend', 'socket', 'upload'], function ($, undefined, Frontend, Socket, Upload) {
    var Controller = {
        kf_code: kf_code,
        name: name,
        avatar: avatar,
        connect_code: '',
        connect_name: '',
        connect_avatar: '',
        port: 9510,

        index: function () {
            var protocol = (window.location.protocol == 'http:') ? 'ws:' : 'wss:';
            Socket.init({
                wsUrl: protocol + '//' + window.location.hostname + ':' + Controller.port,
                HeartBeat: Controller.events.heartBeatData(),  //心跳数据
                timeout: 60000,  //60秒发送心跳包
                onopen: function () {
                    console.log('连接成功');
                    //用户登录
                    Controller.events.kefuLogin();
                },
                onmessage: function (e) {
                    var obj = Controller.events.decode(e.data);
                    return Controller.events[obj.method] ? Controller.events[obj.method].call(this, obj) : true;
                }, onerror: function () {
                    console.log("服务错误....");
                }, onclose: function () {
                    console.log("断开连接");
                }
            });

            //发送消息
            $('.chat-send').on("click", function () {
                var content = $('.message').val();
                if (content.length <= 0 || content == '') {
                    return;
                }
                Controller.events.sendMessage(content);
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
            // 展示大图
            $('.chat-content img').on('click', function () {
                var src = this.src;
                var dom = $('<div style="position:fixed;width:100%;height:100%;z-index: 9999999; background-color: rgba(0, 0, 0, 1); display:flex;justify-content: center;align-items: center"></div>').appendTo('body');
                var img = $('<img src="'+src+'" style="max-height: 100%;max-width: 100%;z-index: 999999999">').appendTo(dom);
                dom.on('click', function(e) {
                    dom.remove();
                    img.remove();
                });
            });

        },
        events: {
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
            kefuLogin: function () {
                Socket.send(this.encode('kefuLogin', {
                    kf_code: Controller.kf_code
                }));
            },
            //上线回调
            online: function (o) {
                console.log(o);
            },
            //发送消息
            sendMessage: function (message) {
                if (!Controller.connect_code) {
                    alert('已断开连接');
                    return;
                }
                Socket.send(this.encode('message', {
                    from_id: Controller.kf_code,
                    from_name: Controller.name,
                    from_avatar: Controller.avatar,
                    to_id: Controller.connect_code,
                    to_name: Controller.connect_name,
                    to_avatar: Controller.connect_avatar,
                    message: message
                }));
            },
            //主动发送消息回调
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