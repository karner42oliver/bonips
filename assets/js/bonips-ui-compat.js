/*
 * boniPS UI Compatibility Layer
 * Replaces required jQuery UI features without external libraries.
 */
(function($) {
	'use strict';

	if (typeof $ === 'undefined') return;

	if (typeof $.ui === 'undefined') $.ui = {};
	if (typeof $.ui.keyCode === 'undefined') {
		$.ui.keyCode = {
			ENTER: 13,
			ESCAPE: 27,
			SPACE: 32
		};
	}

	function makeUiObject($item, extra) {
		var ui = {
			item: $item,
			draggable: $item,
			helper: $item
		};
		if (extra) {
			$.extend(ui, extra);
		}
		return ui;
	}

	var dragState = {
		item: null,
		sender: null,
		originalParent: null,
		originalNext: null,
		cloneMode: false,
		helpPlaceholder: null
	};

	function resetDragState() {
		if (dragState.helpPlaceholder) {
			dragState.helpPlaceholder.remove();
		}
		dragState.item = null;
		dragState.sender = null;
		dragState.originalParent = null;
		dragState.originalNext = null;
		dragState.cloneMode = false;
		dragState.helpPlaceholder = null;
	}

	function canDropInto($target, connectWith, sender) {
		if (!sender || !$target.length) return false;
		if ($target[0] === sender) return true;
		if (!connectWith) return false;
		return $(sender).is(connectWith) && $target.is(connectWith);
	}

	function setSortableItemsDraggable($container, opts) {
		var selector = (opts && opts.items) ? opts.items : '> *';
		$container.children(selector.replace(/^>\s*/, '')).each(function() {
			$(this).attr('draggable', 'true').addClass('ui-draggable ui-sortable-handle');
		});
	}

	$.fn.dialog = function(arg) {
		if (typeof arg === 'string') {
			var method = arg;
			var args = Array.prototype.slice.call(arguments, 1);
			if (method === 'isOpen') {
				var first = this.first();
				var state = first.data('bonipsDialog');
				return !!(state && state.isOpen);
			}

			return this.each(function() {
				var $el = $(this);
				var state = $el.data('bonipsDialog');
				if (!state) return;

				if (method === 'open') {
					state.overlay.toggle(!!state.options.modal);
					state.shell.show();
					state.isOpen = true;
				}
				else if (method === 'close') {
					state.shell.hide();
					state.overlay.hide();
					state.isOpen = false;
				}
				else if (method === 'destroy') {
					state.shell.hide();
					state.overlay.remove();
					$el.detach();
					if (state.next && state.next.length) state.next.before($el);
					else state.parent.append($el);
					state.shell.remove();
					$el.removeData('bonipsDialog');
				}
				else if (method === 'option' && typeof args[0] === 'string') {
					state.options[args[0]] = args[1];
					if (args[0] === 'title') state.title.text(args[1] || '');
				}
			});
		}

		var defaults = {
			autoOpen: true,
			modal: false,
			title: '',
			closeText: 'x',
			width: 'auto'
		};
		var opts = $.extend({}, defaults, arg || {});

		return this.each(function() {
			var $el = $(this);
			var state = $el.data('bonipsDialog');

			if (state) {
				state.options = $.extend({}, state.options, opts);
				state.title.text(state.options.title || '');
				return;
			}

			var $overlay = $('<div class="bonips-ui-overlay" />').appendTo('body');
			var $shell = $('<div class="bonips-ui-dialog-shell" />').appendTo('body');
			var $box = $('<div class="bonips-ui-dialog-box" />').appendTo($shell);
			var $titlebar = $('<div class="bonips-ui-dialog-titlebar" />').appendTo($box);
			var $title = $('<span />').text(opts.title || '').appendTo($titlebar);
			var $close = $('<button type="button" class="bonips-ui-dialog-close" />').text(opts.closeText || 'x').appendTo($titlebar);
			var $content = $('<div class="bonips-ui-dialog-content" />').appendTo($box);

			if (opts.width && opts.width !== 'auto') {
				if (typeof opts.width === 'number') $box.css('width', opts.width + 'px');
				else $box.css('width', opts.width);
			}

			var $parent = $el.parent();
			var $next = $el.next();
			$content.append($el.show());

			state = {
				options: opts,
				overlay: $overlay,
				shell: $shell,
				title: $title,
				parent: $parent,
				next: $next,
				isOpen: false
			};
			$el.data('bonipsDialog', state);

			$close.on('click', function() { $el.dialog('close'); });
			$overlay.on('click', function() { $el.dialog('close'); });

			if (opts.autoOpen) {
				$el.dialog('open');
			} else {
				$shell.hide();
				$overlay.hide();
			}
		});
	};

	$.fn.autocomplete = function(arg) {


		if (typeof arg === 'string') {
			var method = arg;
			var args = Array.prototype.slice.call(arguments, 1);
			if (method === 'option') {
				if (args.length === 1) {
					var state = this.first().data('bonipsAutocomplete');
					return state ? state.options[args[0]] : undefined;
				}
				return this.each(function() {
					var state = $(this).data('bonipsAutocomplete');
					if (!state) return;
					state.options[args[0]] = args[1];
				});
			}
			if (method === 'destroy') {
				return this.each(function() {
					var $el = $(this);
					var state = $el.data('bonipsAutocomplete');
					if (!state) return;
					state.menu.remove();
					$el.off('.bonipsAutocomplete').removeData('bonipsAutocomplete');
				});
			}
			return this;
		}

		var defaults = {
			minLength: 1,
			source: [],
			appendTo: null
		};
		var opts = $.extend({}, defaults, arg || {});

		return this.each(function() {
			var $input = $(this);
			var $menu = $('<ul class="bonips-ui-ac-wrap" />').hide();
			($(opts.appendTo || 'body')).append($menu);

			function closeMenu() {
				$menu.hide().empty();
			}

			function openMenu(items) {
				$menu.empty();
				if (!items || !items.length) {
					closeMenu();
					return;
				}

				$.each(items, function(_, item) {
					var normalized = (typeof item === 'string') ? { label: item, value: item } : item;
					$('<li />').text(normalized.label || normalized.value || '').appendTo($menu).on('mousedown', function(e) {
						e.preventDefault();
						$input.val(normalized.value || normalized.label || '');
						closeMenu();
						$input.trigger('change');
					});
				});

				var offset = $input.offset();
				$menu.css({
					top: (offset.top + $input.outerHeight()) + 'px',
					left: offset.left + 'px',
					width: $input.outerWidth() + 'px'
				}).show();
			}

			function requestSource() {
				var term = $input.val() || '';
				if (term.length < (opts.minLength || 1)) {
					closeMenu();
					return;
				}

				if ($.isArray(opts.source)) {
					var filtered = $.grep(opts.source, function(entry) {
						var value = (typeof entry === 'string') ? entry : (entry.label || entry.value || '');
						return value.toLowerCase().indexOf(term.toLowerCase()) !== -1;
					});
					openMenu(filtered);
					return;
				}

				if (typeof opts.source === 'function') {
					opts.source({ term: term }, function(items) { openMenu(items || []); });
				}
			}

			$input.on('input.bonipsAutocomplete focus.bonipsAutocomplete', requestSource);
			$input.on('blur.bonipsAutocomplete', function() {
				window.setTimeout(closeMenu, 150);
			});

			$(document).on('click.bonipsAutocomplete', function(e) {
				if (!$(e.target).closest($menu).length && !$(e.target).is($input)) closeMenu();
			});

			$input.data('bonipsAutocomplete', {
				options: opts,
				menu: $menu
			});
		});
	};

	$.fn.draggable = function(options) {
		var opts = $.extend({}, options || {});
		return this.each(function() {
			var $el = $(this);
			$el.data('bonipsDraggable', opts);
			$el.attr('draggable', 'true').addClass('ui-draggable');

			$el.off('dragstart.bonipsDraggable dragend.bonipsDraggable');
			$el.on('dragstart.bonipsDraggable', function(event) {
				var e = event.originalEvent;
				var $target = $(event.target);
				if (opts.handle && !$target.closest(opts.handle).length) {
					e.preventDefault();
					return;
				}

				dragState.item = this;
				dragState.sender = this.parentNode;
				dragState.originalParent = this.parentNode;
				dragState.originalNext = this.nextSibling;
				dragState.cloneMode = (opts.helper === 'clone');

				if (e && e.dataTransfer) {
					e.dataTransfer.effectAllowed = 'move';
					e.dataTransfer.setData('text/plain', this.id || 'bonips-drag-item');
				}

				if (typeof opts.start === 'function') {
					opts.start.call(this, event, makeUiObject($(this)));
				}
			});

			$el.on('dragend.bonipsDraggable', function(event) {
				if (typeof opts.stop === 'function') {
					opts.stop.call(this, event, makeUiObject($(this)));
				}
				resetDragState();
			});
		});
	};

	$.fn.droppable = function(options) {
		var opts = $.extend({}, options || {});
		return this.each(function() {
			var $drop = $(this);
			$drop.data('bonipsDroppable', opts);

			$drop.off('dragenter.bonipsDroppable dragover.bonipsDroppable dragleave.bonipsDroppable drop.bonipsDroppable');
			$drop.on('dragenter.bonipsDroppable', function(event) {
				if (!dragState.item) return;
				if (typeof opts.over === 'function') opts.over.call(this, event, { draggable: $(dragState.item) });
			});
			$drop.on('dragover.bonipsDroppable', function(event) {
				if (!dragState.item) return;
				if (typeof opts.accept === 'function' && !opts.accept(dragState.item)) return;
				event.preventDefault();
			});
			$drop.on('dragleave.bonipsDroppable', function(event) {
				if (!dragState.item) return;
				if (typeof opts.out === 'function') opts.out.call(this, event, { draggable: $(dragState.item) });
			});
			$drop.on('drop.bonipsDroppable', function(event) {
				if (!dragState.item) return;
				if (typeof opts.accept === 'function' && !opts.accept(dragState.item)) return;
				event.preventDefault();
				if (typeof opts.drop === 'function') opts.drop.call(this, event, { draggable: $(dragState.item) });
			});
		});
	};

	$.fn.sortable = function(methodOrOptions) {
		if (typeof methodOrOptions === 'string') {
			var method = methodOrOptions;
			var args = Array.prototype.slice.call(arguments, 1);

			if (method === 'toArray') {
				var ids = [];
				this.first().children().each(function() { if (this.id) ids.push(this.id); });
				return ids;
			}

			if (method === 'option') {
				if (args.length === 1) {
					var state = this.first().data('bonipsSortable');
					return state ? state.options[args[0]] : undefined;
				}
				return this.each(function() {
					var state = $(this).data('bonipsSortable');
					if (!state) return;
					state.options[args[0]] = args[1];
					setSortableItemsDraggable($(this), state.options);
				});
			}

			if (method === 'refresh' || method === 'refreshPositions') {
				return this.each(function() {
					var state = $(this).data('bonipsSortable');
					if (!state) return;
					setSortableItemsDraggable($(this), state.options);
				});
			}

			if (method === 'cancel') {
				if (dragState.item && dragState.originalParent) {
					if (dragState.originalNext) $(dragState.originalNext).before($(dragState.item));
					else $(dragState.originalParent).append($(dragState.item));
				}
				return this;
			}

			return this;
		}

		var opts = $.extend({ items: '> *' }, methodOrOptions || {});
		return this.each(function() {
			var $container = $(this);
			$container.data('bonipsSortable', { options: opts });
			setSortableItemsDraggable($container, opts);

			$container.off('dragstart.bonipsSortable dragover.bonipsSortable dragenter.bonipsSortable dragleave.bonipsSortable drop.bonipsSortable');

			$container.on('dragstart.bonipsSortable', opts.items.replace(/^>\s*/, '') || '*', function(event) {
				var $item = $(this);
				dragState.item = this;
				dragState.sender = this.parentNode;
				dragState.originalParent = this.parentNode;
				dragState.originalNext = this.nextSibling;
				dragState.cloneMode = !!($item.data('bonipsDraggable') && $item.data('bonipsDraggable').helper === 'clone');

				if (typeof opts.start === 'function') {
					opts.start.call($container[0], event, makeUiObject($item));
				}
			});

			$container.on('dragenter.bonipsSortable', function(event) {
				if (!dragState.item) return;
				if (!canDropInto($container, opts.connectWith, dragState.sender)) return;
				event.preventDefault();
				if (typeof opts.activate === 'function') opts.activate.call(this, event, makeUiObject($(dragState.item)));
			});

			$container.on('dragleave.bonipsSortable', function(event) {
				if (!dragState.item) return;
				if (typeof opts.deactivate === 'function') opts.deactivate.call(this, event, makeUiObject($(dragState.item)));
			});

			$container.on('dragover.bonipsSortable', function(event) {
				if (!dragState.item) return;
				if (!canDropInto($container, opts.connectWith, dragState.sender)) return;
				event.preventDefault();

				var $children = $container.children(opts.items.replace(/^>\s*/, '') || '*').not(dragState.item);
				var beforeNode = null;
				var y = event.originalEvent.clientY;

				$children.each(function() {
					var rect = this.getBoundingClientRect();
					if (y < rect.top + rect.height / 2) {
						beforeNode = this;
						return false;
					}
				});

				if (dragState.cloneMode) {
					if (!dragState.helpPlaceholder) {
						dragState.helpPlaceholder = $('<div class="widget-placeholder" />').get(0);
					}
					if (beforeNode) $(beforeNode).before(dragState.helpPlaceholder);
					else $container.append(dragState.helpPlaceholder);
					return;
				}

				if (beforeNode) $(beforeNode).before($(dragState.item));
				else $container.append($(dragState.item));
			});

			$container.on('drop.bonipsSortable', function(event) {
				if (!dragState.item) return;
				if (!canDropInto($container, opts.connectWith, dragState.sender)) return;
				event.preventDefault();

				var $item = $(dragState.item);
				if (dragState.cloneMode) {
					$item = $item.clone(true, true);
					$item.removeClass('widget-in-question');
					if (dragState.helpPlaceholder) $(dragState.helpPlaceholder).replaceWith($item);
					else $container.append($item);
				}

				if (typeof opts.receive === 'function' && dragState.sender && dragState.sender !== this) {
					opts.receive.call(this, event, makeUiObject($item, { sender: $(dragState.sender) }));
				}

				if (typeof opts.stop === 'function') {
					opts.stop.call(this, event, makeUiObject($item, { sender: $(dragState.sender) }));
				}

				if (typeof opts.deactivate === 'function') {
					opts.deactivate.call(this, event, makeUiObject($item));
				}

				resetDragState();
			});
		});
	};

})(jQuery);
