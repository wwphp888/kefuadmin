define(["jquery"], function ($) {
    var socket = {
        config: {
            wsUrl: "",
            ShInt: "0",
            timeout: 60000,
            lockReconnect: !1,
            HeartBeat: '{"type":"HeartBeat"}',
            onopen: function () {
                console.log("服务器已连接");
            },
            onmessage: function () {
                console.log("收到消息....");
            },
            onerror: function () {
                console.log("服务错误");
            },
            onclose: function () {
                console.log("服务已关闭");
            }
        },
        init: function(config) {
            var _this = this;
            config = $.extend({}, _this.config, config);
            var ws = new WebSocket(config.wsUrl);
            ws.onopen = function () {
                config.onopen();
                heartCheck.start();
            };
            ws.onmessage = function (e) {
                config.onmessage(e);
                heartCheck.reset();
            };
            ws.onclose = function () {
                config.onclose();
            };
            ws.onerror = function () {
                config.onerror();
            };
            _this.send = function(e) {
                ws.send(e);
            };
            _this.close = function() {
                clearTimeout(heartCheck.timeoutObj);
                clearTimeout(heartCheck.serverTimeoutObj);
                ws.close();
            };
            _this.sendHeartBeat = function() {
                ws.send(config.HeartBeat);
            };
        }
    };
    var heartCheck = {
        timeout: 60000,
        timeoutObj: null,
        serverTimeoutObj: null,
        reset: function () {
            var _this = this;
            clearTimeout(_this.timeoutObj);
            clearTimeout(_this.serverTimeoutObj);
            _this.start();
        },
        start: function () {
            var _this = this;
            _this.timeoutObj = setTimeout(function () {
                socket.sendHeartBeat();
                _this.serverTimeoutObj = setTimeout(function () {
                    socket.close();
                }, _this.timeout);
            }, _this.timeout);
        }
    };
    return socket;
});
