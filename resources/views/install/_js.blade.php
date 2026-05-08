<div id="install-dialog-mask" class="install-dialog-mask" aria-hidden="true">
    <div class="install-dialog">
        <div class="install-dialog-header">{{ __('ptadmin::install.stream.processing') }}</div>
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
        const i18n = {
            requestSent: @json(__('ptadmin::install.stream.request_sent')),
            parseFailed: @json(__('ptadmin::install.stream.parse_failed', ['message' => '__MESSAGE__'])),
            generalFailed: @json(__('ptadmin::install.stream.general_failed')),
            completed: @json(__('ptadmin::install.stream.completed')),
            close: @json(__('ptadmin::install.stream.close')),
            home: @json(__('ptadmin::install.stream.home')),
            admin: @json(__('ptadmin::install.stream.admin')),
            requestFailed: @json(__('ptadmin::install.stream.request_failed', ['status' => '__STATUS__'])),
            streamUnsupported: @json(__('ptadmin::install.stream.stream_unsupported')),
            sendFailed: @json(__('ptadmin::install.stream.send_failed'))
        };

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
            receivedSuccess: false,
            reset: function () {
                this.state = 0;
                this.sendData = null;
                this.receivedSuccess = false;
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
                            message: i18n.parseFailed.replace('__MESSAGE__', error.toString())
                        };
                    }
                }

                if (data.type === 'error') {
                    this.state = 2;
                }
                if (data.type === 'success') {
                    this.receivedSuccess = true;
                }

                this.append(data.type || 'info', data.message || '');
            },
            fail: function () {
                this.append('error', i18n.generalFailed);
                this.renderActions(false);
            },
            success: function () {
                const row = this.append('success', i18n.completed);
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
                closeButton.textContent = isSuccess ? i18n.home : i18n.close;
                closeButton.addEventListener('click', this.reset.bind(this));
                dialogActions.appendChild(closeButton);

                if (!isSuccess) {
                    return;
                }

                const adminButton = document.createElement('button');
                adminButton.type = 'button';
                adminButton.className = 'install-button install-button-primary';
                adminButton.textContent = i18n.admin;
                adminButton.addEventListener('click', function () {
                    const prefix = form.querySelector('input[name="ptadmin_web_prefix"]');
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
            eventAction.process({type: 'info', message: i18n.requestSent});
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
                    if (!response.ok) {
                        throw new Error(i18n.requestFailed.replace('__STATUS__', String(response.status)));
                    }

                    if (!response.body) {
                        throw new Error(i18n.streamUnsupported);
                    }

                    const reader = response.body.getReader();
                    const decoder = new TextDecoder();
                    let buffer = '';

                    function read() {
                        return reader.read().then(function (result) {
                            if (result.done) {
                                flushBuffer();
                                flushRemainingBuffer();
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

                    function flushRemainingBuffer() {
                        const item = buffer.trim();
                        if (item !== '') {
                            eventAction.process(item);
                        }
                        buffer = '';
                    }

                    return read();
                })
                .catch(function (error) {
                    if (eventAction.receivedSuccess) {
                        eventAction.success();
                        return;
                    }
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
                flushRemainingBuffer();
                if (xhr.status < 200 || xhr.status >= 300) {
                    eventAction.process({type: 'error', message: i18n.requestFailed.replace('__STATUS__', String(xhr.status))});
                }
                complete();
            };

            xhr.onerror = function () {
                if (eventAction.receivedSuccess) {
                    eventAction.success();
                    return;
                }
                eventAction.process({type: 'error', message: i18n.sendFailed});
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

            function flushRemainingBuffer() {
                const item = buffer.trim();
                if (item !== '') {
                    eventAction.process(item);
                }
                buffer = '';
            }
        }

        function complete() {
            if (eventAction.state === 2 || !eventAction.receivedSuccess) {
                eventAction.fail();
                return;
            }

            eventAction.success();
        }
    })();
</script>
