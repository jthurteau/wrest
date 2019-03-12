//#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

*******************************************************************************/
var saf = function(){
	
	function javascriptClasses()
	{
		$('.javascriptReveal').removeClass('javascriptReveal');
		$('.javascriptObscure').hide();
		//#TODO oh Foundation...
		$('.javascriptObscure.hide-for-small-only')
		.removeClass('hide-for-small-only').addClass('unhide-for-small-only');
		$('.javascriptObscure.show-for-small-only')
			.removeClass('show-for-small-only').addClass('unshow-for-small-only');
			
			
		$('.javascriptRemove').remove();
		var replaceNodes = $('.javascriptPop');
		for(var i = 0; i < replaceNodes.length; i++) {
			var replaceNode = $(replaceNodes[i]);
			var replacements = replaceNode.children();
			replacements.insertAfter(replaceNode);
			replaceNode.remove();					
		}
		$('.javascriptDisable input, '
			+ '.javascriptDisable textarea, '
			+ '.javascriptDisable select, '
			+ 'input.javascriptDisable, '
			+ 'select.javascriptDisable select, '
			+ 'textarea.javascriptDisable'
		).prop('disabled', true);
		
		$('.javascriptFocus').first().focus();
		$('.javascriptSmallFullwidth, .javascriptSmall-12')
			.removeClass('small-1 small-2 small-3 small-4 small-6 small-6 small-7 small-8 small-9 small-10 small-11')
			.addClass('small-12');
		$('.javascriptSmall-7')
		.removeClass('small-1 small-2 small-3 small-4 small-6 small-6 small-8 small-9 small-10 small-11 small-12')
		.addClass('small-7');
	}
	
	var editor = function()
	{
		var keyboardFocus = null;
		var keyboardListener = null;
		var activeEditor = null;
		var saveButton = null;
		var cancelButton = null;
		var oldContents = '';

		function openEditBox() 
		{
			activeEditor = $(this);
			activeEditor.addClass('active');
			oldContents = new String(activeEditor.html());
			activeEditor.html(explodeEditBoxHtml(oldContents));
			activeEditor.unbind('click',openEditBox);
			keyboardListener = editBoxKey;
			if (true) {
				activeEditor.attr('tabindex','-1');
				keyboardFocus = activeEditor;
			} else {
				keyboardFocus = $(document);
			}
			keyboardFocus.on('keydown', keyboardListener);
			saveButton = $('.saveButton', activeEditor);
			cancelButton = $('.cancelButton', activeEditor);
			$('.spacerBefore').on('click',cursorToStart);
			$('.spacerAfter').on('click',cursorToEnd);
			saveButton.on('click',storeEditBox);
			cancelButton.on('click',restoreEditBox);
			$('.character',activeEditor).on('click',setCursor);
			$('.spacerBefore, .spacerAfter, .character').height(activeEditor.height());
			return false;
		}

		function closeEditBox()
		{
			if (activeEditor) {
				activeEditor.on('click',openEditBox);
				activeEditor.removeClass('active');
				keyboardFocus.unbind('keydown',keyboardListener);
				keyboardListener = null;
				saveButton = null;
				cancelButton = null;
				activeEditor = null;
				oldContents = '';
			}			
		}

		function editBoxKey(event)
		{
			event.preventDefault();
			switch(event.which) {
				case 16:
				case 17:
				case 18:
				case 13:
					return false;
					break;				
				case 37:
					return cursorLeft();
					break;
				case 39:
					return cursorRight();
					break;
				case 40:
				case 38:
					return false;
					break;
				case 8:
					//delete
				case 46:
					//delete
				case 32:
					return cursorInsert(' ');
				default:
					return cursorInsert(characterMap(event.which, event.shiftKey));
			}
			return false;
		}

		function characterMap(key, isShifted)
		{
			var map = {
				48 : ')',
				49 : '!',
				50 : '@',
				51 : '#',
				52 : '$',
				53 : '%',
				54 : '^',
				55 : '&',
				56 : '*',
				57 : '(',
				189 : '_',
				187 : '+',
				219 : '{',
				221 : '}',
				220 : '|',
				186 : ':',
				222 : '"',
				188 : '<',
				190 : '>',
				191 : '?',
				192 : '~'
			};
			var alternateMap = {
				189 : '-',
				187 : '=',
				219 : '[',
				221 : ']',
				220 : "\\",
				186 : ';',
				222 : "'",
				188 : ',',
				190 : '.',
				191 : '/',
				192 : '`'
			};
			
			if(key >= 60 && key <= 90) {
				return(
					isShifted
					? String.fromCharCode(key)
					: String.fromCharCode(key).toLowerCase()
				);
			} else if(map.hasOwnProperty(key)) {
				return(
					isShifted
					? map[key]
					: (
						alternateMap.hasOwnProperty(key)
						? alternateMap[key]
						: String.fromCharCode(key)
					)
				);
			} else {
				alert(key);
			}
		}

		function cursorToStart()
		{
			event.preventDefault();
			var allCharacters = activeEditor.children('.character');
			allCharacters.removeClass('cursorBefore');
			allCharacters.removeClass('cursorAfter');
			allCharacters.first().addClass('cursorBefore');
			event.stopPropagation();
		}


		function cursorToEnd()
		{
			event.preventDefault();
			var allCharacters = activeEditor.children('.character');
			allCharacters.removeClass('cursorBefore');
			allCharacters.removeClass('cursorAfter');
			allCharacters.last().addClass('cursorAfter');
			event.stopPropagation();
		}
		function cursorLeft()
		{
			var currentCharacter = $('.cursorBefore');
			if (!currentCharacter.length) {
				currentCharacter = $('.cursorAfter');
				currentCharacter.removeClass('cursorAfter');
				currentCharacter.addClass('cursorBefore');
				return false;
			}
			previousCharacter = currentCharacter.prev('.character');
			if (previousCharacter.length) {
				currentCharacter.removeClass('cursorBefore');
				previousCharacter.addClass('cursorBefore');
			}
		}

		function cursorRight()
		{
			var currentCharacter = $('.cursorBefore');
			if (!currentCharacter.length) {
				return false;
			}
			nextCharacter = currentCharacter.next('.character');
			if (nextCharacter.length) {
				currentCharacter.removeClass('cursorBefore');
				nextCharacter.addClass('cursorBefore');
			} else {
				currentCharacter.removeClass('cursorBefore');
				currentCharacter.addClass('cursorAfter');
			}
			return false;
		}

		function cursorInsert(character)
		{
			var insertHtml = generateCharacterTag(character);
			var currentCharacter = $('.cursorBefore');
			if (!currentCharacter.length) {
				currentCharacter = $('.cursorAfter');
				currentCharacter.after($(insertHtml).on('click',setCursor));
				nextCharacter = currentCharacter.next('.character');
				currentCharacter.removeClass('cursorAfter');
				nextCharacter.addClass('cursorAfter');
				return false;
			}
			currentCharacter.before($(insertHtml).on('click',setCursor));
			return false;

		}

		function setCursor()
		{
			event.preventDefault();
			var currentCharacter = $(this);
			var allCharacters = currentCharacter.parent().children();
			allCharacters.removeClass('cursorBefore');
			allCharacters.removeClass('cursorAfter');
			currentCharacter.addClass('cursorBefore');
			event.stopPropagation();
		}

		function restoreEditBox(event)
		{
			event.preventDefault();
			activeEditor.html(oldContents.toString());
			closeEditBox();
			event.stopPropagation();
			//return false;
		}

		function storeEditBox(event)
		{
			event.preventDefault();
			activeEditor.html(collapseEditBoxHtml(activeEditor));
			closeEditBox();
			event.stopPropagation();
			//return false;
		}

		function collapseEditBoxHtml(node)
		{
			var newOutput = '';
			var allChildren = $(node).children('.character');
			if (0 == allChildren.length) {
				return '';
			}
			for(child = 0; child < allChildren.length; child++) {
				var oldString = $(allChildren[child]).html();
				var isSpace = $(allChildren[child]).hasClass('space');
				var newCharacter = '';
				switch (oldString){
					case '&gt;':
						newCharacter = '>';
						break;
					case '&lt;':
						newCharacter = '<';
						break;
					case '&quot':
						newCharacter = '"';
						break;
					case "&apos":
						newCharacter = "'";
						break;
					case '&amp;':
						newCharacter = '&';
						break;
					default:
						newCharacter = (isSpace ? ' ' : oldString);
				}
				newOutput = newOutput + newCharacter;
			}
			return newOutput;
		}

		function explodeEditBoxHtml(htmlString)
		{
			var newOutput = '<span class="editButton saveButton"><span class="buttonText">Save</span></span><span class="spacer spacerBefore"></span>';
			for(character in htmlString){
				var oldCharacter = htmlString[character];
				var newString = generateCharacterTag(oldCharacter);
				newOutput = newOutput + newString;
			}
			return newOutput + '<span class="spacer spacerAfter"></span><span class="editButton cancelButton"><span class="buttonText">Cancel</span></span>';
		}

		function generateCharacterTag(character)
		{
			switch (character){
				case '>':
					return '<span class="character tagStart">&gt;</span>';
					break;
				case '<':
					return '<span class="character tagEnd">&lt;</span>';
					break;
				case '"':
					return '<span class="character">&quot;</span>';
					break;
				case "'":
					return '<span class="character">&apos;</span>';
					break;
				case '&':
					return '<span class="character">&amp;</span>';
					break;
				case ' ':
					return '<span class="character space"></span>';
					break;
				default:
					return '<span class="character">' + character +'</span>';
			}
		}

		return {
			init : function (node) {
				if (node) {
					$(node).on('click',openEditBox);
				} else {
					$('.editBox').on('click',openEditBox);
				}
				$(document).on('click',restoreEditBox);
			}
		}
	}();

	var debug = function(){
		var shown = false;
		return {
			init: function()
			{
				$('#showDebug').click(function() {
					if (!shown) {
						$('.debugError, .debugWarning, .debugNotice, .debugStatus, .debugOther').show();
						shown = true;
					} else {
						$('.debugError, .debugWarning, .debugNotice, .debugStatus, .debugOther').hide();
						shown = false;
					}
					return false;
				});
				$('.debugExpand',$('.debugError, .debugWarning, .debugNotice, .debugStatus, .debugOther')).click(function(eventObject){
					var clickTarget = $(eventObject.currentTarget);
					var debugBlock = clickTarget.parents('.debugError, .debugWarning, .debugNotice, .debugStatus, .debugOther').first();
					var expandTarget = $('.debugTrace', debugBlock);
					expandTarget.toggle();
//					var hide = expandTarget.data('visible');
//					if (hide) {
//						expandTarget.show();
//						expandTarget.data('visible', false);
//					} else {
//						expandTarget.hide();
//						expandTarget.data('visible', true);
//					}
				});
			}			
		};
	}();

	var options = {
		baseUrl : '/'
	};
	
	return {
		init : function(newOptions)
		{
			for(data in newOptions) {
				options[data] = newOptions[data];
			}
			//editor.init();
			debug.init();
			javascriptClasses();
		},
		getBaseUrl : function()
		{
			return options['baseUrl'];
		}
	};
}();
