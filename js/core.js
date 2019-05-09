var snowgirlCore = function (config, vocabulary) {
    this.initErrorHandler();
    this.initArgs(config, vocabulary);
    this.initQuery();
    this.initDOM();
    this.initCallbacks();
//    this.gtag.sendPageView();
    return this.export();
};
snowgirlCore.prototype.initArgs = function (config, vocabulary) {
    this.selectorHeader = '.header';
    this.selectorHeaderWeb = this.selectorHeader + ' .header-web';
    this.selectorHeaderWebSearch = this.selectorHeaderWeb + ' .header-search';
//     this.selectorHeaderNav = this.selectorHeader + ' .header-nav';
    this.selectorHeaderLogo = this.selectorHeader + ' .header-logo';
    this.selectorBtnToggle = '[class*="btn-toggle"]';
    this.config = config || {};
    this.vocabulary = vocabulary || {};
    this.gtag = this.getGTag();
    this.errorHandler = null;
    this.$document = $(document);
    this.$body = $('body');
    this.windowResizeCallbacks = [];
    this.storage = this.getStorage();
    this.translator = this.getTranslator();
    this.session = this.getSession();
};
snowgirlCore.prototype.export = function () {
    return {
        export: $.proxy(this.export, this),
        getConfig: $.proxy(this.getConfig, this),
        getLoadingObject: $.proxy(this.getLoadingObject, this),
        storage: this.storage,
        gtag: this.gtag,
        translator: this.translator,
        session: this.session,
        setErrorHandler: $.proxy(this.setErrorHandler, this),
        makeRequest: $.proxy(this.makeRequest, this),
        makeRequestByRoute: $.proxy(this.makeRequestByRoute, this),
        getCurrentLocale: $.proxy(this.getCurrentLocale, this),
        getScriptLoader: $.proxy(this.getScriptLoader, this),
        $document: this.$document,
        $body: this.$body,
        getUriByRoute: $.proxy(this.getUriByRoute, this),
        getImageSource: $.proxy(this.getImageSource, this),
        registerWidget: $.proxy(this.registerWidget, this),
        addWindowResizeCallback: $.proxy(this.addWindowResizeCallback, this),
        syncSessionData: $.proxy(this.syncSessionData, this),
        try: $.proxy(this.try, this)
    };
};
snowgirlCore.prototype.initErrorHandler = function () {
    window.onerror = $.proxy(function (text, url, line) {
        this.gtag.sendEvent('exception', {
            description: text + ' in ' + url + ' on ' + line,
            fatal: true
        });

        this.errorHandler && this.errorHandler(text, url, line);

        return false;
    }, this);
};
snowgirlCore.prototype.initQuery = function () {
    var _core = this;

//     this.xhr = [];
//     this.xhr.abort = function () {
//         for (var i = 0, l = this.length; i < l; i++) {
//             this[i].abort();
//         }
//     };
//
//     $.ajaxSetup({
//         beforeSend: $.proxy(function (xhr) {
//             this.xhr.push(xhr);
//         }, this),
//         complete: $.proxy(function (xhr) {
//             var index = this.xhr.indexOf(xhr);
//
//             if (index > -1) {
//                 this.xhr.splice(index, 1);
//             }
//         }, this)
//     });

    jQuery.fn.isVisible = function () {
        var $this = $(this);

        if (!$this.length) {
            return false;
        }

        if (!$this.is(':visible')) {
            return false;
        }

        var st = $(window).scrollTop();
        var ot = $this.offset().top;

        return ot >= st && ((ot + $this.height()) <= (st + $(window).height()));
    };
    jQuery.fn.toggleLoading = function (text) {
        $(this).each(function () {
            var $this = $(this);
            var originalText;

            if ($this.data('loading')) {
                if ($this.data('origin').icon) {
                    originalText = $this.data('origin').text;
                    $this.html('<span class="' + $this.data('origin').icon + '"></span>' + (originalText.length ? (' ' + originalText) : ''));
                } else {
                    $this.html($this.data('origin').text)
                }

                $this.removeClass('disabled')
                    .attr('disabled', null);
                $this.data('loading', false);
            } else {
                originalText = $.trim($this.text());
                $this.data('origin', {icon: $this.find('span').attr('class'), text: originalText})
                    .html('<span class="fa fa-refresh fa-spin"></span>' + (originalText.length ? (' ' + (text ? text : 'Загружаю') + '...') : ''))
                    .addClass('disabled')
                    .attr('disabled', 'disabled');
                $this.data('loading', true);
            }

            return $this;
        });
        return this;
    };
    jQuery.fn.getButton = function () {
        return this.closestUp('.btn');
    };
    jQuery.fn.closestDown = function (arg) {
        var output = this.find(arg);
        return this.is(arg) ? output.add(this) : output;
    };
    jQuery.fn.closestUp = function (arg) {
        return this.closest(arg);
    };
    jQuery.fn.submitByAjax = function (thenFn, catchFn) {
        var raw = this.serializeArray();
        var data = {};

        for (var i = 0, s = raw.length; i < s; i++) {
            data[raw[i]['name']] = raw[i]['value'];
        }

        var $btn = this.find('button[type=submit]').toggleLoading();
        return _core.makeRequest(this.attr('action'), this.attr('method'), data)
            .then(function (body) {
                $btn.toggleLoading();
                thenFn && thenFn(body);
            })
            .catch(function (body) {
                $btn.toggleLoading();
                catchFn && catchFn(body);
            });
    };
    jQuery.fn.togglePending = function () {
        $(this).each(function () {
            var $this = $(this);

            if ($this.hasClass('pending')) {
                $this.removeClass('pending').find('.pending-overlay').remove();
            } else {
                $this.addClass('pending').append('<div class="pending-overlay"></div>');
            }

            return $this;
        });
        return this;
    };
};
snowgirlCore.prototype.initDOM = function () {
    if (document.referrer.indexOf(location.host) < 0) {
        $('.btn-back').remove();
    }

//     this.hideMeta();
};
snowgirlCore.prototype.hideMeta = function () {
    setTimeout(function () {
        $('.meta:last').css('display', 'none');
    }, 2000);
};
snowgirlCore.prototype.initCallbacks = function () {
    $(window)
        .resize($.proxy(this.onWindowResize, this))
        .resize($.proxy(this.onWindowUnload, this))
        .trigger('resize');

    this.$document
        .on('focus', 'input[placeholder], textarea[placeholder]', function () {
            if (!this.getAttribute('data-placeholder')) {
                this.setAttribute('data-placeholder', this.getAttribute('placeholder'));
            }

            this.removeAttribute('placeholder');
        })
        .on('blur', '[data-placeholder]', function () {
            this.setAttribute('placeholder', this.getAttribute('data-placeholder'));
        })
        .on('click', this.selectorBtnToggle, $.proxy(this.onBtnToggleClick, this))
        .on('click', this.selectorHeaderLogo + ' ' + this.selectorBtnToggle, $.proxy(this.onHeaderBtnToggleClick, this))
        .on('click', this.selectorHeaderLogo + ' [class*="btn-toggle-"][class*="-web"]', $.proxy(this.onHeaderBtnToggleWebClick, this))
        .on('click', this.selectorHeader + ' .btn-bookmark', $.proxy(this.onBookmarkMeClick, this))
        .on('click', '.btn-back', function () {
            if (('history' in window) && history.length) {
                history.back();
            } else {
                location.href = document.referrer;
            }
        });
};
snowgirlCore.prototype.addWindowResizeCallback = function (callback, trigger) {
    this.windowResizeCallbacks.push(callback);

    if (trigger) {
        callback(window.innerWidth);
    }
};
snowgirlCore.prototype.onWindowResize = function () {
    var width = window.innerWidth;

    for (var i = 0, s = this.windowResizeCallbacks.length; i < s; i++) {
        this.windowResizeCallbacks[i](width);
    }
};
snowgirlCore.prototype.onWindowUnload = function () {
//     this.xhr.abort();
//     console.log(this.xhr);
//     alert('ok');
};
snowgirlCore.prototype.getScriptLoader = function () {
    return this.scriptLoader ? this.scriptLoader : this.scriptLoader = new (function (_config) {
        var start = [], finish = [], callback = {}, config = {
            counters: {
                js: 1,
                css: 1
            }
        };

        $.extend(true, config, _config);

        function getFileName(file, ext) {
            return file + (file.indexOf('?') === -1 ? '?' : '&') + '___=' + config.counters[ext];
        }

        function get(list, fn) {
            fn = fn || function () {
            };
            if (list.length) {
                var file = list.shift();

                if (finish.indexOf(file) !== -1) {
                    return get(list, fn);
                }

                if (start.indexOf(file) !== -1) {
                    callback[file].push(function () {
                        return get(list, fn);
                    });

                    return false;
                } else {
                    start.push(file);
                }

                var node;
                if (/js(\?|$)/.test(file)) {
                    node = document.createElement('script');
                    node.setAttribute('async', '');
                    node.setAttribute('type', 'text/javascript');
                    node.setAttribute('src', getFileName(file, 'js'));
                } else if (/css(\?|$)/.test(file)) {
                    node = document.createElement('link');
                    node.setAttribute('rel', 'stylesheet');
                    node.setAttribute('type', 'text/css');
                    node.setAttribute('href', getFileName(file, 'css'));
                } else {
                    return get(list, fn);
                }

                callback[file] = [];
                callback[file].push(function () {
                    return get(list, fn);
                });
                node.onload = function () {
                    finish.push(file);

                    for (var i = 0; i < callback[file].length; i++) {
                        callback[file][i]();
                    }
                };
                document.getElementsByTagName('head')[0].appendChild(node);
            } else {
                fn();
            }

            return false;
        }

        return {get: get};
    })(this.getConfig('script_loader'));
};
snowgirlCore.prototype.getLoadingObject = function (text) {
    return new ($.proxy(function (text) {
        var $this = $('<div/>', {class: 'loading'})
            .append($('<span/>', {class: 'icon fa fa-circle-o-notch fa-spin'}))
            .append($('<span/>').text((text || 'Загружаю') + '...'));

        this.$body.append($this);

        var remove = function () {
            $this.remove();
        };

        return {remove: remove};
    }, this))(text);
};
snowgirlCore.prototype.getGTag = function () {
    var object = function () {
//        this.gtag = this.getTag();
//        this.trackingId = this.getTrackingId();
    };
    object.prototype.getTag = function () {
        if (
            window.hasOwnProperty('gtag') &&
            'function' === typeof window.gtag
        ) {
            return window.gtag;
        }

        return false;
    };
    object.prototype.getTrackingId = function () {
        if (
            window.hasOwnProperty('dataLayer') &&
            'undefined' !== typeof window.dataLayer[1] &&
            'undefined' !== typeof window.dataLayer[1][0] &&
            'config' === window.dataLayer[1][0] &&
            'undefined' !== typeof window.dataLayer[1][1] &&
            /^UA-/.test(window.dataLayer[1][1])
        ) {
            return window.dataLayer[1][1];
        }

        return false;
    };
    object.prototype.sendEvent = function (event, params) {
        params = params || {};

        var gtag = this.getTag();
        var trackingId = this.getTrackingId();

        var params2 = {};

        for (var key in params) {
            if (params.hasOwnProperty(key)) {
                if ('_callback' === key) {
                    params[key] = (function (callback) {
                        var called = false;

                        function fn() {
                            if (!called) {
                                called = true;
                                callback();
                            }
                        }

                        setTimeout(fn, 1000);
                        return fn;
                    })(params[key]);
                }

                params2[('_' === key.charAt(0) ? 'event' : '') + key] = params[key];
            }
        }

        if (gtag && trackingId) {
            gtag('event', event, params2);
        } else {
//            console.log(event, params2);

            if (
                params2.hasOwnProperty('event_callback') &&
                'function' === typeof params2['event_callback']
            ) {
                params2['event_callback']();
            }
        }
    };

    return new object();
};
snowgirlCore.prototype.getStorage = function () {
    var object = function () {
    };

    object.prototype.set = function (k, v) {
        if (window.hasOwnProperty('localStorage')) {
            window.localStorage.setItem(k, v);
        } else if (window.hasOwnProperty('sessionStorage')) {
            window.sessionStorage.setItem(k, v);
        }
    };

    object.prototype.get = function (k, d) {
        if (window.hasOwnProperty('localStorage')) {
            var v = window.localStorage.getItem(k);
            return null === v ? d : v;
        }

        if (window.hasOwnProperty('sessionStorage')) {
            var v = window.sessionStorage.getItem(k);
            return null === v ? d : v;
        }

        return (typeof d === 'undefined') ? null : d;
    };

    return new object();
};
snowgirlCore.prototype.getTranslator = function () {
    var object = function (vocabulary) {
        this.load(vocabulary);
    };

    object.prototype.load = function (vocabulary) {
        $.extend(true, this, vocabulary);
    };

    object.prototype._ = function (k, arg) {
        if (!this.hasOwnProperty(k)) {
            return k;
        }

        var v = this[k];

        if (typeof v === 'string') {
            var m = v.match(/%d|%s/g);

            if (m) {
                for (var i = 0; i < m.length; i++) {
                    v = v.replace(m[i], m[i] === '%d' ? parseInt(arguments[i + 1]) : arguments[i + 1].toString());
                }
            }
        }

        return v;
    };

    return new object(this.vocabulary);
};
snowgirlCore.prototype.setErrorHandler = function (handler) {
    this.errorHandler = handler;
};
snowgirlCore.prototype.getConfig = function (k, def) {
    return k in this.config ? this.config[k] : (def ? def : null);
};
snowgirlCore.prototype.makeRequest = function (url, method, data, dataType) {
    var _this = this;

    return new Promise(function (resolve, reject) {
        dataType = dataType || 'json';
        method = method || 'get';
        data = data || {};

        var ajax = jQuery.ajax({
            url: url,
            dataType: dataType,
            type: method,
            data: data
        });

        if (!_this.getConfig('doNotShowLoadingOnRequests', false)) {
            var loading = _this.getLoadingObject();

            ajax.always(function () {
                loading.remove();
            });
        }

        ajax.done(function (data, status) {
            resolve(data, status);
        });

        ajax.fail(function (xhr, status, error) {
            _this.gtag.sendEvent('error', {
                _category: 'errors',
                _action: 'server',
                _label: method.toUpperCase() + ' ' + url + ' with ' + $.param(data)
            });

//            console.log(error, status);
            reject(error, status);
        });
    });
};
snowgirlCore.prototype.getUriByRoute = function (route, params, domain) {
    route = this.getConfig('routes')[route || 'default'];
    params = params || {};

    var append = [];

    for (var i in params) {
        if (params.hasOwnProperty(i)) {
            if (route.indexOf(':' + i) >= 0) {
                route = route.replace(new RegExp(':' + i), params[i]);
            } else {
                if ($.isArray(params[i])) {
                    for (var j = 0, l = params[i].length; j < l; j++) {
                        append.push(i + '[]=' + params[i][j]);
                    }
                } else {
                    append.push(i + '=' + params[i]);
                }
            }
        }
    }

    route = route.replace(/\/:[a-z_]+$/, '');

    if (append.length) {
        route += '?' + append.join('&');
    }

    return this.getUriByPath(route, domain);
};
snowgirlCore.prototype.makeRequestByRoute = function (route, params, method, data, dataType, domain) {
    return this.makeRequest(this.getUriByRoute(route, params, domain), method, data, dataType);
};

snowgirlCore.prototype.getUriByPath = function (path, domain) {
    return this.getConfig('domains')[domain || 'master'] + '/' + path.replace(/^[\/]+/, '');
};
snowgirlCore.prototype.onBookmarkMeClick = function () {
    this.gtag.sendEvent('add_to_bookmark', {
        _category: 'bookmarks',
        _action: 'add'
    });

    if (window.sidebar && window.sidebar.addPanel) {
        window.sidebar.addPanel(document.title, window.location.href, '');
    } else if (window.external && ('AddFavorite' in window.external)) {
        window.external.AddFavorite(location.href, document.title);
    } else if (window.opera && window.print) {
        this.title = document.title;
    } else {
        alert('Нажмите ' + (navigator.userAgent.toLowerCase().indexOf('mac') !== -1 ? 'Command/Cmd' : 'CTRL') + ' + D что-бы сохранить сайт в закладках');
        $(this).blur();
    }
};
snowgirlCore.prototype.onHeaderBtnToggleClick = function (ev, stop) {
    if (stop) {
        ev.preventDefault();
        return false;
    }

    var $this = $(ev.target).getButton();
    var $buttons = $this.closestUp(this.selectorHeaderLogo).find(this.selectorBtnToggle).not($this);

    $buttons.each(function (i, o) {
        var $o = $(o);

        if ($o.hasClass('active')) {
            $o.trigger('click', true);
        }
    });
};
snowgirlCore.prototype.onHeaderBtnToggleWebClick = function () {
    var $headerSearch = $(this.selectorHeaderWebSearch);

    var $headerFormInputGroup = $headerSearch.find('.input-group');

    $headerFormInputGroup.removeClass('input-group-sm').addClass('input-group-lg');
    $headerFormInputGroup.find('input').removeClass('input-sm').addClass('input-lg');

    $headerSearch.find('.btn-sm').each(function () {
        $(this).removeClass('btn-sm').addClass('btn-lg');
    });

    $headerSearch.find('input[name=query]').trigger('focus');
};
snowgirlCore.prototype.onBtnToggleClick = function (ev) {
    var $this = $(ev.target).getButton();
    var cls = $this.attr('class');
    var matches = cls.match(/btn-toggle-([a-z]+)-([a-z\-]+)/);

//     console.log(matches);

    if (matches) {
        var device = matches[1];
        var obj = matches[2];
//         var expanded = this.hasClass('toggle-on');

        var $icon = $this.find('span');
        var $target = $('.obj-toggle-' + device + '-' + obj);

        $target.toggleClass('toggle-on');
        $this.toggleClass('active');
        var tmp = $this.data('icon-toggle');
        $this.data('icon-toggle', $icon.attr('class'));
        $icon.attr('class', tmp);
//         $this.attr('aria-expanded', expanded ? 'false' : 'true');
    }
};
snowgirlCore.prototype.getCurrentLocale = function (isLong) {
    var locale = this.session['client'] ? this.session['client']['language'] : 'ru';
    return isLong ? (locale + '_' + locale.toUpperCase()) : locale;
};
snowgirlCore.prototype.getSession = function () {
    var object = function (data) {
    };

    object.prototype.load = function (data) {
        $.extend(true, this, data);
    };

    return new object(this.getConfig('session'));
};
snowgirlCore.prototype.getImageSource = function (id, format, param) {
//    FORMAT_NONE = 0;
//    FORMAT_HEIGHT = 1;
//    FORMAT_WIDTH = 2;
//    FORMAT_CAPTION = 3;
//    FORMAT_AUTO = 4;
    return this.getUriByRoute('image', {
        format: format || 0,
        param: param || 0,
        file: id
    }, 'static') + '.jpg';
};
snowgirlCore.prototype.registerWidget = function (name, parentOrObject, object) {
    var args = $.makeArray(arguments);
    args[0] = 'snowgirl.' + args[0];

    if (3 === args.length) {
        args[1] = $['snowgirl'][args[1]];
    }

    return $.widget.apply(null, args);
};
snowgirlCore.prototype.syncSessionData = function (data, fn) {
    return this.makeRequestByRoute('default', {action: 'sync-session-data'}, 'post', {data: data})
        .then(function () {
            fn && fn();
        })
        .catch(function () {
            alert('Произошла ошибка( Пожалуйста, повторите попытку');
        });
};
snowgirlCore.prototype.try = function (fn, interval, count) {
    fn = fn || function () {
    };
    interval = interval || 100;
    count = count || 25;

    var tmpCount = 0;
    var int = setInterval(function () {
        if (true === fn() || count === tmpCount) {
            clearInterval(int);
        }
        tmpCount++;
    }, interval);
};

snowgirlCore = new snowgirlCore(window['snowgirl_config']);

var T = function () {
    return snowgirlCore.translator._.apply(snowgirlCore.translator, Array.prototype.slice.call(arguments, 0));
};