// class
function SiteConn() {
    //TODO: turn this off
    var DEBUG = true;

    var self = this,
        socket,
        buffer = [],
        events = [];

    var pingInterval;
    var eventListeners = [];

    function initWebSocket() {
        socket = new WebSocket('ws://' + SiteConn.HOST + ':' + SiteConn.PORT);

        socket.onopen = function() {
            if (DEBUG)
                console.log('Соединение установлено');

            var message;

            if (buffer.length) {
                while (message = buffer.shift()) {
                    socket.send(message);
                    if (DEBUG)
                        console.log('Сообщение из буфера отправлено', message);
                }
            }
        };

        socket.onclose = function(event) {
            if (DEBUG) {
                if (event.wasClean) {
                    console.log('Соединение закрыто');
                } else {
                    console.log('Обрыв соединения');
                    console.log('Код: ' + event.code + ' причина: ' + event.reason);
                }
            }
            
            clearInterval(pingInterval);
            setTimeout(initWebSocket, SiteConn.RECONNECT_INTERVAL);
        };

        socket.onmessage = function(event) {
            var data = JSON.parse(event.data);

            for (var i in events[data.opcode]) {
                events[data.opcode][i].call(self, data.data);
            }
        }

        socket.onerror = function(error) {
            if (DEBUG)
                console.log('Socket Error: ' + error.message);
        };

        pingInterval = setInterval(function() {
            socket.send(JSON.stringify({opcode: 'cmsg_ping', data: {}}));
        }, SiteConn.PING_INTERVAL);
    }

    function send(message) {
        if (socket.readyState === 1) {
            socket.send(message);
            if (DEBUG) console.log('Сообщение отправлено', message);
        } else {
            buffer.push(message);
            if (DEBUG) console.log('Сообщение помещено в буфер', message);
        }
    }

    this.send = send;

    // public properties/functions
    Object.defineProperties(self, {
        // properties

        addEventListener: {
            enumerable: true,
            configurable: false,
            value: function (opcode, listener) {
                opcode = opcode.split(':');
                var event = opcode;

				if (events[event] === undefined) {
					events[event]	= [];
				}

				if (listener instanceof Function) {
					if (events[event].indexOf(listener) < 0)
						events[event].push(listener);
					else
						throw new opcodeError('Listener is defined');
				} 
				else
					throw new opcodeError('Listener must be a Function');
            }
        },
        removeEventListener: {
            enumerable: true,
            configurable: false,
            value: function (opcode, listener) {
                opcode = opcode.split(':');
                var event = opcode,
                    listeners = events[event];

                if (listeners !== undefined && listener instanceof Function) {
                    var index = listeners.indexOf(listener);
                    if (index >= 0) listeners.splice(index, 1);
                }
            }
        }
    });

    initWebSocket();
};

// SiteConn constants
Object.defineProperties(SiteConn, {
    HOST: {
        enumerable: true,
        configurable: false,
        value: document.location.host
    },
    PORT: {
        enumerable: true,
        configurable: false,
        value: 8080
    },
    RECONNECT_INTERVAL: {
        enumerable: true,
        configurable: false,
        value: 5000
    },
    PING_INTERVAL: {
        enumerable: true,
        configurable: false,
        value: 20000
    }
});

conn = new SiteConn();