<div id="install-dialog-mask" class="install-dialog-mask" aria-hidden="true">
    <div class="install-dialog">
        <div class="install-dialog-header">正在执行安装程序</div>
        <div id="install-console" class="install-console"></div>
        <div id="install-dialog-actions" class="install-dialog-actions" style="display: none;"></div>
    </div>
</div>
<script>
    (function () {
        const url = @json(route('ptadmin.install.stream'));
        const successUrl = @json(url('/'));
        const form = document.getElementById('install-form');
        const submitButton = document.getElementById('submit');
        const dialogMask = document.getElementById('install-dialog-mask');
        const consoleBox = document.getElementById('install-console');
        const dialogActions = document.getElementById('install-dialog-actions');

        if (!form || !submitButton || !dialogMask || !consoleBox || !dialogActions) {
            return;
        }

        const stateMap = {
            error: 'error',
            success: 'success',
            process: 'process',
            info: 'info'
        };

        const eventAction = {
            state: 0,
            sendData: null,
            reset: function () {
                this.state = 0;
                this.sendData = null;
                consoleBox.innerHTML = '';
                dialogActions.innerHTML = '';
                dialogActions.style.display = 'none';
                dialogMask.classList.remove('is-visible');
                dialogMask.setAttribute('aria-hidden', 'true');
                submitButton.disabled = false;
                submitButton.classList.remove('is-disabled');
            },
            open: function () {
                dialogMask.classList.add('is-visible');
                dialogMask.setAttribute('aria-hidden', 'false');
                submitButton.disabled = true;
                submitButton.classList.add('is-disabled');
            },
            append: function (type, message) {
                const row = document.createElement('div');
                row.className = 'install-console-item';
                row.innerHTML = '<span class="install-console-badge ' + (stateMap[type] || 'info') + '">' + type + '</span><div>' + message + '</div>';
                consoleBox.appendChild(row);
                consoleBox.scrollTop = consoleBox.scrollHeight;
                return row;
            },
            process: function (payload) {
                let data = payload;
                if (typeof data === 'string') {
                    try {
                        data = JSON.parse(data);
                    } catch (error) {
                        data = {
                            type: 'error',
                            message: '解析失败: ' + error.toString()
                        };
                    }
                }

                if (data.type === 'error') {
                    this.state = 2;
                }

                this.append(data.type || 'info', data.message || '');
            },
            fail: function () {
                this.append('error', '安装失败，请检查上方日志后重试。');
                this.renderActions(false);
            },
            success: function () {
                const row = this.append('success', '安装完成，准备跳转。');
                this.renderActions(true);

                let timer = 8;
                const timerNode = document.createElement('strong');
                timerNode.style.marginLeft = '6px';
                timerNode.textContent = String(timer);
                row.querySelector('div').appendChild(timerNode);

                const countdown = window.setInterval(function () {
                    timer -= 1;
                    timerNode.textContent = String(timer);
                    if (timer <= 0) {
                        window.clearInterval(countdown);
                        window.location.href = successUrl;
                    }
                }, 1000);
            },
            renderActions: function (isSuccess) {
                dialogActions.style.display = 'flex';
                dialogActions.innerHTML = '';

                const closeButton = document.createElement('button');
                closeButton.type = 'button';
                closeButton.className = 'install-button install-button-secondary';
                closeButton.textContent = isSuccess ? '返回首页' : '关闭窗口';
                closeButton.addEventListener('click', this.reset.bind(this));
                dialogActions.appendChild(closeButton);

                if (!isSuccess) {
                    return;
                }

                const adminButton = document.createElement('button');
                adminButton.type = 'button';
                adminButton.className = 'install-button install-button-primary';
                adminButton.textContent = '进入管理后台';
                adminButton.addEventListener('click', function () {
                    const prefix = form.querySelector('input[name="app_system_prefix"]');
                    window.location.href = '/' + (prefix && prefix.value ? prefix.value : '');
                });
                dialogActions.appendChild(adminButton);
            }
        };

        submitButton.addEventListener('click', function () {
            eventAction.reset();
            eventAction.open();
            eventAction.state = 1;
            eventAction.sendData = new FormData(form);
            eventAction.process({type: 'info', message: '发送安装请求'});
            eventAction.send(eventAction.sendData);
        });

        eventAction.send = function (formData) {
            if (window.fetch && window.ReadableStream) {
                fetchStream(formData);
                return;
            }

            xhrStream(formData);
        };

        function fetchStream(formData) {
            fetch(url, {method: 'POST', body: formData})
                .then(function (response) {
                    if (!response.body) {
                        throw new Error('浏览器不支持流式响应');
                    }

                    const reader = response.body.getReader();
                    const decoder = new TextDecoder();
                    let buffer = '';

                    function read() {
                        return reader.read().then(function (result) {
                            if (result.done) {
                                flushBuffer();
                                complete();
                                return;
                            }

                            buffer += decoder.decode(result.value, {stream: true});
                            flushBuffer();

                            return read();
                        });
                    }

                    function flushBuffer() {
                        const chunks = buffer.split('\n\n');
                        buffer = chunks.pop();
                        chunks.forEach(function (chunk) {
                            const item = chunk.trim();
                            if (item !== '') {
                                eventAction.process(item);
                            }
                        });
                    }

                    return read();
                })
                .catch(function (error) {
                    eventAction.process({type: 'error', message: error.toString()});
                    eventAction.fail();
                });
        }

        function xhrStream(formData) {
            const xhr = new XMLHttpRequest();
            let lastProcessedIndex = 0;
            let buffer = '';

            xhr.onprogress = function () {
                buffer += xhr.responseText.substring(lastProcessedIndex);
                lastProcessedIndex = xhr.responseText.length;
                flushBuffer();
            };

            xhr.onload = function () {
                flushBuffer();
                complete();
            };

            xhr.onerror = function () {
                eventAction.process({type: 'error', message: '安装请求发送失败'});
                eventAction.fail();
            };

            xhr.open('POST', url, true);
            xhr.send(formData);

            function flushBuffer() {
                const chunks = buffer.split('\n\n');
                buffer = chunks.pop();
                chunks.forEach(function (chunk) {
                    const item = chunk.trim();
                    if (item !== '') {
                        eventAction.process(item);
                    }
                });
            }
        }

        function complete() {
            if (eventAction.state === 2) {
                eventAction.fail();
                return;
            }

            eventAction.success();
        }
    })();
</script>
