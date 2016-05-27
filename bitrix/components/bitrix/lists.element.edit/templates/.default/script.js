BX.ListsElementEditClass = (function ()
{
	var ListsElementEditClass = function (parameters)
	{
		this.randomString = parameters.randomString;
		this.ajaxUrl = '/bitrix/components/bitrix/lists.element.edit/ajax.php';
		this.urlTabBp = parameters.urlTabBp;
		this.iblockTypeId = parameters.iblockTypeId;
		this.iblockId = parameters.iblockId;
		this.elementId = parameters.elementId;
		this.socnetGroupId = parameters.socnetGroupId;
		this.sectionId = parameters.sectionId;
	};

	ListsElementEditClass.prototype.completeWorkflow = function(workflowId, action)
	{
		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.addToLinkParam(this.ajaxUrl, 'action', 'completeWorkflow'),
			data: {
				workflowId: workflowId,
				iblockTypeId: this.iblockTypeId,
				elementId: this.elementId,
				iblockId: this.iblockId,
				socnetGroupId: this.socnetGroupId,
				sectionId: this.sectionId,
				action: action,
				sessid: BX.bitrix_sessid()
			},
			onsuccess: BX.delegate(function (result)
			{
				if(result.status == 'success')
				{
					this.showModalWithStatusAction({
						status: 'success',
						message: result.message
					});
					setTimeout(document.location.href = this.urlTabBp, 1000);
				}
				else
				{
					result.errors = result.errors || [{}];
					this.showModalWithStatusAction({
						status: 'error',
						message: result.errors.pop().message
					})
				}
			}, this)
		});
	};

	ListsElementEditClass.prototype.addToLinkParam = function (link, name, value)
	{
		if (!link.length) {
			return '?' + name + '=' + value;
		}
		link = BX.util.remove_url_param(link, name);
		if (link.indexOf('?') != -1) {
			return link + '&' + name + '=' + value;
		}
		return link + '?' + name + '=' + value;
	};

	ListsElementEditClass.prototype.showModalWithStatusAction = function (response, action)
	{
		response = response || {};
		if (!response.message) {
			if (response.status == 'success') {
				response.message = BX.message('LISTS_STATUS_ACTION_SUCCESS');
			}
			else {
				response.message = BX.message('LISTS_STATUS_ACTION_ERROR') + '. ' + this.getFirstErrorFromResponse(response);
			}
		}
		var messageBox = BX.create('div', {
			props: {
				className: 'bx-lists-alert'
			},
			children: [
				BX.create('span', {
					props: {
						className: 'bx-lists-aligner'
					}
				}),
				BX.create('span', {
					props: {
						className: 'bx-lists-alert-text'
					},
					text: response.message
				}),
				BX.create('div', {
					props: {
						className: 'bx-lists-alert-footer'
					}
				})
			]
		});

		var currentPopup = BX.PopupWindowManager.getCurrentPopup();
		if(currentPopup)
		{
			currentPopup.destroy();
		}

		var idTimeout = setTimeout(function ()
		{
			var w = BX.PopupWindowManager.getCurrentPopup();
			if (!w || w.uniquePopupId != 'bx-lists-status-action') {
				return;
			}
			w.close();
			w.destroy();
		}, 3500);
		var popupConfirm = BX.PopupWindowManager.create('bx-lists-status-action', null, {
			content: messageBox,
			onPopupClose: function ()
			{
				this.destroy();
				clearTimeout(idTimeout);
			},
			autoHide: true,
			zIndex: 2000,
			className: 'bx-lists-alert-popup'
		});
		popupConfirm.show();

		BX('bx-lists-status-action').onmouseover = function (e)
		{
			clearTimeout(idTimeout);
		};

		BX('bx-lists-status-action').onmouseout = function (e)
		{
			idTimeout = setTimeout(function ()
			{
				var w = BX.PopupWindowManager.getCurrentPopup();
				if (!w || w.uniquePopupId != 'bx-lists-status-action') {
					return;
				}
				w.close();
				w.destroy();
			}, 3500);
		};
	};

	ListsElementEditClass.prototype.addNewTableRow = function(tableID, col_count, regexp, rindex)
	{
		var tbl = document.getElementById(tableID);
		var cnt = tbl.rows.length;
		var oRow = tbl.insertRow(cnt);

		for(var i=0;i<col_count;i++)
		{
			var oCell = oRow.insertCell(i);
			var html = tbl.rows[cnt-1].cells[i].innerHTML;
			oCell.innerHTML = html.replace(regexp,
				function(html)
				{
					return html.replace('[n'+arguments[rindex]+']', '[n'+(1+parseInt(arguments[rindex]))+']');
				}
			);
		}
	};

	ListsElementEditClass.prototype.elementDelete = function(form_id, message)
	{
		var _form = document.getElementById(form_id);
		var _flag = document.getElementById('action');
		if(_form && _flag)
		{
			if(confirm(message))
			{
				_flag.value = 'delete';
				_form.submit();
			}
		}
	};

	ListsElementEditClass.prototype.createAdditionalHtmlEditor = function(tableId, fieldId, formId)
	{
		var tbl = document.getElementById(tableId);
		var cnt = tbl.rows.length;
		var oRow = tbl.insertRow(cnt);
		var oCell = oRow.insertCell(0);
		var sHTML = tbl.rows[cnt - 1].cells[0].innerHTML;
		var p = 0, s, e, n;
		while (true)
		{
			s = sHTML.indexOf('[n', p);
			if (s < 0)
				break;
			e = sHTML.indexOf(']', s);
			if (e < 0)
				break;
			n = parseInt(sHTML.substr(s + 2, e - s));
			sHTML = sHTML.substr(0, s) + '[n' + (++n) + ']' + sHTML.substr(e + 1);
			p = s + 1;
		}
		p = 0;
		while (true)
		{
			s = sHTML.indexOf('__n', p);
			if (s < 0)
				break;
			e = sHTML.indexOf('_', s + 2);
			if (e < 0)
				break;
			n = parseInt(sHTML.substr(s + 3, e - s));
			sHTML = sHTML.substr(0, s) + '__n' + (++n) + '_' + sHTML.substr(e + 1);
			p = e + 1;
		}
		oCell.innerHTML = sHTML;

		var idEditor = 'id_'+fieldId+'__n'+cnt+'_';
		var fieldIdName = fieldId+'[n'+cnt+'][VALUE]';
		window.BXHtmlEditor.Show({
			'id':idEditor,
			'inputName':fieldIdName,
			'name' : fieldIdName,
			'content':'',
			'width':'100%',
			'height':'200',
			'allowPhp':false,
			'limitPhpAccess':false,
			'templates':[],
			'templateId':'',
			'templateParams':[],
			'componentFilter':'',
			'snippets':[],
			'placeholder':'Text here...',
			'actionUrl':'/bitrix/tools/html_editor_action.php',
			'cssIframePath':'/bitrix/js/fileman/html_editor/iframe-style.css?1412693817',
			'bodyClass':'',
			'bodyId':'',
			'spellcheck_path':'/bitrix/js/fileman/html_editor/html-spell.js?v=1412693817',
			'usePspell':'N',
			'useCustomSpell':'Y',
			'bbCode': false,
			'askBeforeUnloadPage':false,
			'settingsKey':'user_settings_1',
			'showComponents':true,
			'showSnippets':true,
			'view':'wysiwyg',
			'splitVertical':false,
			'splitRatio':'1',
			'taskbarShown':false,
			'taskbarWidth':'250',
			'lastSpecialchars':false,
			'cleanEmptySpans':true,
			'lazyLoad':false,
			'showTaskbars':false,
			'showNodeNavi':false,
			'controlsMap':[
				{'id':'Bold','compact':true,'sort':'80'},
				{'id':'Italic','compact':true,'sort':'90'},
				{'id':'Underline','compact':true,'sort':'100'},
				{'id':'Strikeout','compact':true,'sort':'110'},
				{'id':'RemoveFormat','compact':true,'sort':'120'},
				{'id':'Color','compact':true,'sort':'130'},
				{'id':'FontSelector','compact':false,'sort':'135'},
				{'id':'FontSize','compact':false,'sort':'140'},
				{'separator':true,'compact':false,'sort':'145'},
				{'id':'OrderedList','compact':true,'sort':'150'},
				{'id':'UnorderedList','compact':true,'sort':'160'},
				{'id':'AlignList','compact':false,'sort':'190'},
				{'separator':true,'compact':false,'sort':'200'},
				{'id':'InsertLink','compact':true,'sort':'210','wrap':'bx-htmleditor-'+formId},
				{'id':'InsertImage','compact':false,'sort':'220'},
				{'id':'InsertVideo','compact':true,'sort':'230','wrap':'bx-htmleditor-'+formId},
				{'id':'InsertTable','compact':false,'sort':'250'},
				{'id':'Code','compact':true,'sort':'260'},
				{'id':'Quote','compact':true,'sort':'270','wrap':'bx-htmleditor-'+formId},
				{'id':'Smile','compact':false,'sort':'280'},
				{'separator':true,'compact':false,'sort':'290'},
				{'id':'Fullscreen','compact':false,'sort':'310'},
				{'id':'BbCode','compact':true,'sort':'340'},
				{'id':'More','compact':true,'sort':'400'}],
			'autoResize':true,
			'autoResizeOffset':'40',
			'minBodyWidth':'350',
			'normalBodyWidth':'555'
		});
		var htmlEditor = BX.findChildrenByClassName(BX(tableId), 'bx-html-editor');
		for(var k in htmlEditor)
		{
			var editorId = htmlEditor[k].getAttribute('id');
			var frameArray = BX.findChildrenByClassName(BX(editorId), 'bx-editor-iframe');
			if(frameArray.length > 1)
			{
				for(var i = 0; i < frameArray.length - 1; i++)
				{
					frameArray[i].parentNode.removeChild(frameArray[i]);
				}
			}

		}
	};

	return ListsElementEditClass;
})();
