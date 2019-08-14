/**
 * ...
 * @author Tarwin
 */

fanplayrJQuery.postJSON = function(url, data, callback) {
    return fanplayrJQuery.ajax({
        'type': 'POST',
        'url': url,
        'data': data,
        'dataType': 'json',
        'success': callback
    });
};

// we need this crap for cookies - at least to be lazy
if (typeof String.prototype.trimLeft !== "function") {
	String.prototype.trimLeft = function() {
		return this.replace(/^\s+/, "");
	};
}
if (typeof String.prototype.trimRight !== "function") {
	String.prototype.trimRight = function() {
		return this.replace(/\s+$/, "");
	};
}
if (typeof Array.prototype.map !== "function") {
	Array.prototype.map = function(callback, thisArg) {
		for (var i=0, n=this.length, a=[]; i<n; i++) {
			if (i in this) a[i] = callback.call(thisArg, this[i]);
		}
		return a;
	};
}

 if ( ! window.Fanplayr ) {
	window.Fanplayr = {
		isWorking: false,
		proxy : function (fn, obj) {
			return function() { fn.apply(obj, arguments); };
		},

		getParams : function() {
			var a = window.location.search.substr(1).split("&");
			if (a == "") return {};
			var b = {};
			for (var i = 0; i < a.length; ++i) {
				var p = a[i].split("=");
				if (p.length != 2) continue;
				b[p[0]] = decodeURIComponent(p[1].replace(/\+/g, " "));
			}
			return b;
		},

		getParam : function( name, defValue ) {
			var params = this.getParams();
			if (params.hasOwnProperty(name)) {
				return params[name];
			}
			return defValue;
		},

		disableLogs : function() {
			var empty = function() {};
			Fanplayr.log = Fanplayr.debug = Fanplayr.info = Fanplayr.warn = Fanplayr.error = empty;
		},

		unlink: function(){
			var url = Fanplayr.configShopUrl + 'fanplayr/compy/unlink/';
			fanplayrJQuery.postJSON(url, {
				acckey: Fanplayr.configAccKey,
				secret: Fanplayr.configSecret
			})
				.success(function(result) {
					if (!result.error){
						alert('Account unlinked. Refreshing page.');
						window.top.location.reload();
					}else{
						alert(result.message);
					}
				})
				.error(function() {
					alert('Sorry, there was a communications error. Refreshing page.');
					window.top.location.reload();
				});
		},

		fillCampaignList : function($cnt, data) {

			if (!data) return;

			var $ = fanplayrJQuery;

			if (data.error){
				$cnt.html("Sorry there was an error getting your campaign data. " + data.message);
			}else{
				function CampList() {
					var obj = {
						init : function() {
							this.$cnt = $cnt;
							var out = '';
							var outD = '';
							var outP = '';
							var outR = '';

							Fanplayr.numGenius = 0;

							for (i=0; i<data.campaigns.length; i++){
								camp = data.campaigns[i];
								var sId = camp.shopIntegrationId;
								var isIntegrated = sId != '' && sId != 'null' && sId != null;
								var id = 'fanplayr-int-' + camp.key;
								var cStatus = camp.status;
								var tOut = '<tr class="fanplayr-camplist-row fanplayr-status-' + cStatus;
								tOut += (isIntegrated ? ' fanplayr-is-added ' : ' fanplayr-not-added ');

								if (camp.type == 'genius'){
									tOut += '" id="' + id + '" data-key="' + camp.key + '" data-type="' + camp.type + '"><td class="fanplayr-type-genius">' + camp.name + '</td><td align="right"><a class="fanplayr-list-button" href="http://my.fanplayr.com/dashboard.genius.site/campaignId/' + camp.id + '/" target="_blank"><div class="fanplayr-icon fanplayr-edit"></div><span>View / Edit</span></a></td></tr>';
								}else{
									tOut += '" id="' + id + '" data-key="' + camp.key + '" data-type="' + camp.type + '"><td class="fanplayr-type-sales">' + camp.name + '</td><td align="right"><a class="fanplayr-list-button fanplayr-add-remove-button" href="#"><div class="fanplayr-add-remove fanplayr-icon"></div><span>Activate</span></a><a class="fanplayr-list-button" href="http://my.fanplayr.com/dashboard.campaign.overview/campaignId/' + camp.id + '/" target="_blank"><div class="fanplayr-icon fanplayr-edit"></div><span>Edit</span></a></td></tr>';
								}

								if (cStatus == 'draft') outD += tOut;
								if (cStatus == 'published') outP += tOut;
								if (cStatus == 'running') outR += tOut;

								if ((cStatus == 'published' || cStatus == 'running') && camp.type == 'genius' && isIntegrated)
									Fanplayr.numGenius++;
							}

							// only add headings if some exist
							if (outD.length)
								out += '<tr class="fanplayr-camplist-header"><td colspan="2">Drafts (you have to publish these before you can add them)</td></tr>' + outD;
							if (outP.length)
								out += '<tr class="fanplayr-camplist-header"><td colspan="2">Scheduled</td></tr>' + outP;
							if (outR.length)
								out += '<tr class="fanplayr-camplist-header"><td colspan="2">Currently Running</td></tr>' + outR;

							Fanplayr.hasDraftCampaigns = outD.length > 0;
							Fanplayr.hasPublishedCampaigns = outP.length > 0;
							Fanplayr.hasRunningCampaigns = outR.length > 0;

							this.$cnt.html(out);

							this.$cnt.find('.fanplayr-status-draft .fanplayr-add-remove-button').hide();
							this.$cnt.find('.fanplayr-is-added a.fanplayr-add-remove-button span').text('Deactivate');

							this.$cnt.on("click", "a.fanplayr-add-remove-button", $.proxy(this.onAddRemove, this));

							return this;
						},

						onAddRemove: function(e) {
							e.preventDefault();

							if (Fanplayr.isWorking){
								alert('Please wait until the current task is complete.');
								return;
							}

							var $t = $(e.currentTarget);
							var $row = $t.closest('tr');
							var key = $row.data('key');
							var type = $row.data('type');

							// not already pressed
							if (!$t.hasClass('fanplayr-activity')){
								$t.addClass('fanplayr-activity');
								$t.find('span').text('Working ...');
								var added = $t.closest('tr').hasClass('fanplayr-is-added');

								if (!added){
									if (type == 'genius' && Fanplayr.numGenius > 0){
										$t.removeClass('fanplayr-activity');
										$t.find('span').text('Activate');
										alert('You can only have one Smart + Targeted campaign running. Please disable other campaigns first.');
										return;
									}

									// add
									Fanplayr.isWorking = true;
									var url = Fanplayr.configShopUrl + 'fanplayr/compy/addwidget/';
									$.postJSON(url, {
										acckey: Fanplayr.configAccKey,
										secret: Fanplayr.configSecret,
										campkey: key,
										inform: '1',
										type: type
									})
										.success(function(result) {
											Fanplayr.isWorking = false;
											if (!result.error){
												$t.removeClass('fanplayr-activity');
												$row.removeClass('fanplayr-not-added').addClass('fanplayr-is-added');
												$t.find('span').text('Deactivate');
												if (type == 'genius') Fanplayr.numGenius++;
											}else{
												$t.removeClass('fanplayr-activity');
												$t.find('span').text('Activate');
												alert(result.message);
											}
										})
										.error(function() {
											Fanplayr.isWorking = false;
											$t.removeClass('fanplayr-activity');
											$t.find('span').text('Activate');
											alert('Sorry, there was a communications error.');
										});
								}else{
									// remove
									Fanplayr.isWorking = true;
									var url = Fanplayr.configShopUrl + 'fanplayr/compy/removewidget/';
									$.postJSON(url, {
										acckey: Fanplayr.configAccKey,
										secret: Fanplayr.configSecret,
										campkey: key,
										inform: '1',
										type: type
									})
										.success(function(result) {
											Fanplayr.isWorking = false;
											if (!result.error){
												$t.removeClass('fanplayr-activity');
												$row.removeClass('fanplayr-is-added').addClass('fanplayr-not-added');
												$t.find('span').text('Activate');
												if (type == 'genius') Fanplayr.numGenius--;
											}else{
												$t.removeClass('fanplayr-activity');
												$t.find('span').text('Deactivate');
												alert(result.message);
											}
										})
										.error(function() {
											Fanplayr.isWorking = false;
											$t.removeClass('fanplayr-activity');
											$t.find('span').text('Deactivate');
											alert('Sorry, there was a communications error.');
										});
								}
							}
						}
					}
					return obj.init();
				}

				var campList = new CampList();

				//fanplayr-add-remove-removed
			}
		},

		addTemplates: function(){
			var url = Fanplayr.configShopUrl + 'fanplayr/compy/addtemplate/';
			fanplayrJQuery.postJSON(url, {
				acckey: Fanplayr.configAccKey,
				secret: Fanplayr.configSecret
			})
				.success(function(result) {
					if (!result.error){
						alert(result.message);
						window.top.location.reload();
					}else{
						alert(result.message);
					}
				})
				.error(function() {
					alert('Sorry, there was a communications error. Refreshing page.');
					window.top.location.reload();
				});
		},

		console:
		{
			show: function(){
				Fanplayr.setCookie('fanplayrsocialcoupons-console-display', 'show');
				document.getElementById('fanplayrsocialcoupons-console').style.display = 'block';
			},
			hide: function(){
				Fanplayr.setCookie('fanplayrsocialcoupons-console-display', 'hide');
				document.getElementById('fanplayrsocialcoupons-console').style.display = 'none';
			},
			save: function(){
				if (Fanplayr.isWorking){
					alert('Please wait until the current task is complete.');
					return;
				}

				var $ = fanplayrJQuery;
				Fanplayr.isWorking = true;

				$('#fanplayrsocialcoupons-console-saving').show();

				// if we specify a custom URL, use that to update as this IS where our shop is
				if ($('#fanplayrsocialcoupons-console-url').val() != '')
					Fanplayr.configShopUrl = $('#fanplayrsocialcoupons-console-url').val();

				var url = Fanplayr.configShopUrl + 'fanplayr/compy/consoleupdate/';

				$.postJSON(url, {
					acckey: Fanplayr.configAccKey,
					secret: Fanplayr.configSecret,
					secretinner: Fanplayr.configSecretInner,
					acckeynew: $('#fanplayrsocialcoupons-console-acckey').val(),
					secretnew: $('#fanplayrsocialcoupons-console-secret').val(),
					shopid: $('#fanplayrsocialcoupons-console-shopid').val(),
					gamafied: $('#fanplayrsocialcoupons-console-gamafied').val(),
					genius: $('#fanplayrsocialcoupons-console-snt').val(),
					url: $('#fanplayrsocialcoupons-console-url').val(),
					disableonurls: $('#fanplayrsocialcoupons-console-disableonurls').val(),
					embedtype: $('#fanplayrsocialcoupons-console-embedtype').val(),
					layouthook: $('#fanplayrsocialcoupons-console-layouthook').val(),
					layouthookhome: $('#fanplayrsocialcoupons-console-layouthookhome').val(),
					layouthookorder: $('#fanplayrsocialcoupons-console-layouthookorder').val(),
					depprefix: $('#fanplayrsocialcoupons-console-depprefix').val(),
					deproutes: $('#fanplayrsocialcoupons-console-deproutes').val(),
					customembedurl: $('#fanplayrsocialcoupons-console-customembedurl').val(),
					customembedurlpost: $('#fanplayrsocialcoupons-console-customembedurlpost').val(),
					gtmcontainerid: $('#fanplayrsocialcoupons-console-gtmcontainerid').val(),
					usetbuy: $('#fanplayrsocialcoupons-console-usetbuy').val()
				})
					.success(function(result) {
						Fanplayr.isWorking = false;
						$('#fanplayrsocialcoupons-console-saving').hide();
						if (!result.error){
							alert('Saved. Refreshing page.');
							window.top.location.reload();
						}else{
							alert(result.message);
						}
					})
					.error(function() {
						Fanplayr.isWorking = false;
						$('#fanplayrsocialcoupons-console-saving').hide();
						alert('Sorry, there was a communications error.');
					});
			}
		},

		setCookie: function(name, value, days)
		{
			var exdate = new Date();
			exdate.setDate(exdate.getDate() + days);
			var value = escape(value) + ((days == null) ? "" : "; expires=" + exdate.toUTCString());
			document.cookie = name + "=" + value;
		},

		getCookies: function() {
			var c = document.cookie, v = 0, cookies = {};
			if (document.cookie.match(/^\s*\$Version=(?:"1"|1);\s*(.*)/)) {
				c = RegExp.$1;
				v = 1;
			}
			if (v === 0) {
				c.split(/[,;]/).map(function(cookie) {
					var parts = cookie.split(/=/, 2),
						name = decodeURIComponent(parts[0].trimLeft()),
						value = parts.length > 1 ? decodeURIComponent(parts[1].trimRight()) : null;
					cookies[name] = value;
				});
			} else {
				c.match(/(?:^|\s+)([!#$%&'*+\-.0-9A-Z^`a-z|~]+)=([!#$%&'*+\-.0-9A-Z^`a-z|~]*|"(?:[\x20-\x7E\x80\xFF]|\\[\x00-\x7F])*")(?=\s*[,;]|$)/g).map(function($0, $1) {
					var name = $0,
						value = $1.charAt(0) === '"'
								  ? $1.substr(1, -1).replace(/\\(.)/g, "$1")
								  : $1;
					cookies[name] = value;
				});
			}
			return cookies;
		},

		getCookie: function(name) {
			return getCookies()[name];
		}
	};

	// Logging methods.
	// ----------------------------------------
	(function() {
		var levels = ["log", "debug", "info", "warn", "error"];
		var disableLog = true;
		if ( ! disableLog) {
			try {
				var matt = top.console.log;
				top.console.log.apply(top.console, ['Log enabled!']);
			} catch (err) {
				disableLog = true;
			}
		}
		if ( "console" in window && ! disableLog ) {
			if ( "firebug" in console ) {
				for (var i in levels) Fanplayr[levels[i]] = top.console[levels[i]];
			} else {
				var empty = function() {};
				// Chrome
				Fanplayr.log = (top.console.log) ? function() { top.console.log.apply(top.console, arguments); } : empty;
				Fanplayr.debug = (top.console.debug) ? function() { top.console.debug.apply(top.console, arguments); } : empty;
				Fanplayr.info = (top.console.info) ? function() { top.console.info.apply(top.console, arguments); } : empty;
				Fanplayr.warn = (top.console.warn) ? function() { top.console.warn.apply(top.console, arguments); } : empty;
				Fanplayr.error = (top.console.error) ? function() { top.console.error.apply(top.console, arguments); } : empty;
			}
		} else {
			var empty = function() {
				var args = [];
				for (i=0;i<arguments.length;i++) args.push(arguments[i]);
				console.log(args);
			};
			for (var i in levels) Fanplayr[levels[i]] = empty;
		}
	})();
};

 if ( ! window.FanplayrClass ) {
	// Author: Steffen Rusitschka
	// http://www.ruzee.com/blog/2008/12/javascript-inheritance-via-prototypes-and-closures
	(function(){
		var isFn = function(fn) { return typeof fn == "function"; };
		FanplayrClass = function(){};
		FanplayrClass.create = function(proto) {
			var k = function(magic) { // call init only if there's no magic cookie
				if (magic != isFn && isFn(this.init)) this.init.apply(this, arguments);
			};
			k.prototype = new this(isFn); // use our private method as magic cookie
			for (key in proto) (function(fn, sfn){ // create a closure
				k.prototype[key] = !isFn(fn) || !isFn(sfn) ? fn : // add _super method
				function() { this._super = sfn; return fn.apply(this, arguments); };
			})(proto[key], k.prototype[key]);
			k.prototype.constructor = k;
			k.extend = this.extend || this.create;
			return k;
		};
		// Wrap fanplayrJQuery event binding.
		FanplayrClass.prototype.on = function( events, selector, data, handler ) {
			if ( ! this.__$ ) this.__$ = fanplayrJQuery("<div></div>");
			this.__$.on.call(this.__$, events, selector, data, fanplayrJQuery.proxy(handler, this) );
		};
		FanplayrClass.prototype.off = function( events, selector, handler ) {
			if ( ! this.__$ ) this.__$ = fanplayrJQuery("<div></div>");
			this.__$.off.call(this.__$, events, selector, handler );
		};
		// Wrap fanplayrJQuery event invoking.
		FanplayrClass.prototype.trigger = function( eventType, extraParameters ) {
			if ( ! this.__$ ) this.__$ = fanplayrJQuery("<div></div>");
			this.__$.trigger.call(this.__$, eventType, extraParameters );
		};
	})();
};

var FanplayrModal = FanplayrClass.create({
	id : null,
	$el : null,
	$iframe : null,
	$backdrop : null,
	iframe : null,
	defaults : {
		url			: false,
		close		: false,
		data		: null,
		maxWidth	: 0,
		maxHeight	: 0
	},
	_close : false,
	data : null,
	contexts : {},
	init : function( id ) {
		if ( id == undefined ) Fanplayr.warn("Must create a modal with an ID!");
		this.id = id;
		FanplayrModal.register(id, this);
	},
	create : function () {
		if ( ! this.$el ) {
			var html = '<div class="fanplayr-modal fanplayr-modal-frame">'
							+ '<div class="icon-load"></div>'
							+ '<div class="fanplayr-modal-inner"><iframe src="" frameborder="0"></iframe></div>'
						+ '</div>';
			this.$el = fanplayrJQuery(html).appendTo("body");
			this.$iframe = this.$el.find("iframe");
			this.$iframe.load(fanplayrJQuery.proxy(this.loaded, this));
			this.$backdrop = fanplayrJQuery('<div class="fanplayr-modal-backdrop" />').appendTo("body");
			this.$el.find('.fanplayr-modal-inner').hide();
		}
	},
	show : function() {
		this.$backdrop.show();
		this.$iframe.css("visibility", "hidden");
		this.$el.css("width", 76).css("height", 76).css("marginLeft", -38).css("marginTop", -38);
		this.$el.show();
		// stop body scroll
		//$('body').css('overflow', 'hidden');
		//$('html').css('overflow', 'hidden');
	},
	load : function (url) {
		if ( ! this.$el ) {
			var $el = fanplayrJQuery("#fanplayr-modal");
			if ( $el.length == 0 ) {
				this.create();
			} else {
				this.$el = $el;
			}
		}

		var options;

		this._close = false;

		if (typeof url == "string") {
			options = fanplayrJQuery.extend({}, this.defaults);
			options.url = url;
		} else {
			options			= fanplayrJQuery.extend({}, this.defaults, url);
			this.options	= options;
		}

		if ( options.close ) {
			this._close = options.close;
		}

		this.data = (options.data != null && options.data != undefined) ? options.data : null;

		this.show();
		this.$el.addClass("loading");
		this.$iframe.attr("src", options.url);
	},
	loaded : function () {

		this.iframe = this.$iframe.get()[0].contentWindow;

		//this.iframe.FanplayrModal.instance = this;

		this.$el.removeClass("loading");
		this.$el.find('.fanplayr-modal-inner').show();

		this.autoSize();
	},
	autoSize: function(_w, _h) {
		this.$iframe.css("width", 10).css("height", 10);

		var w = (_w == undefined) ? this.$iframe.contents().width() : _w;
		var h = (_h == undefined) ? this.$iframe.contents().height() : _h;

		//if (this.options.maxWidth && this.options.maxWidth > 0) w = Math.min(w, this.options.maxWidth);
		//if (this.options.maxHeight && this.options.maxHeight > 0) h = Math.min(h, this.options.maxHeight);

		this.$el.css("width", w).css("height", h).css("marginLeft", -w/2).css("marginTop", -h/2);
		this.$iframe.css("width", w).css("height", h);
		this.$iframe.hide().css("visibility", "visible").fadeIn();
	},
	close : function () {
		this.$el.hide();
		this.$backdrop.hide();
		if ( this._close ) {
			this._close();
		}
		//$('body').css('overflow', 'auto');
		//$('html').css('overflow', 'auto');
	}
});

FanplayrModal.modals = {};

FanplayrModal.init = function( id ) {
	fanplayrJQuery(document).ready(function() {
		Fanplayr.log("modal init!");
		if ( id == undefined ) id = "main";
		var modal = FanplayrModal.parent(id);
		fanplayrJQuery("*[data-modal-action='close']").click(function(event) {
			Fanplayr.log("data-modal-action!!!!");
			//modal.close();
			event.preventDefault();
		});
	});
};

FanplayrModal.register = function( id, modal ) {
	//Fanplayr.log("register modal: ", id);
	FanplayrModal.modals[id] = modal;
};

FanplayrModal.getById = function( id ) {
	if ( FanplayrModal.modals[id] ) {
		return FanplayrModal.modals[id];
	}
	return null;
};

FanplayrModal.context = function( id ) {
	if ( window.parent.FanplayrModal ) {
		if ( id == undefined ) id = "main";
		return window.parent.FanplayrModal.getById(id);
	}
	return null;
};