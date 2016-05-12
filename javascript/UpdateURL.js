
/**
 * Implements a form/prompt that queries the user with options to update fields to match new page title.
 */

var $jq = jQuery;

Behaviour.register({
	
	'input#Form_EditForm_Title': {

		onchange: function() {
			
			if(this.value.length == 0) return;
			//if(!$('Form_EditForm_URLSegment')) return;
			
			// Controls the initial behaviour of the checkboxes
			var checkURL = true;
			var checkMenu = true;
			var checkMeta = true;
			
			var urlSegmentField = $('Form_EditForm_URLSegment');
			
			// Code to replicate default silverstripe behaviour: not all options are checked by default
			/* 
			if(urlSegmentField.value.startsWith("new")) {
				checkURL = true;
			}
			If you type in Page name, the Navigation Label and Meta Title should automatically update the first time
			if($('Form_EditForm_MetaTitle') && $('Form_EditForm_MenuTitle').value.startsWith("New")) {
				checkMenu = true;
			}
			
			if($('Form_EditForm_MetaTitle') && $('Form_EditForm_MetaTitle').value.length == 0 ) {
				checkMeta = true;
			}
			*/
			
			
			// insert the update fields form
			// only show checkboxes if the field exists.
			updateFieldsPrompt(!!urlSegmentField,!!$('Form_EditForm_MenuTitle'),!!$('Form_EditForm_MetaTitle'));
			
			// Set the initial state of check boxes
			jq('#boxMatchURL').attr('checked', checkURL);
			jq('#boxMatchMenu').attr('checked', checkMenu);
			jq('#boxMatchMeta').attr('checked', checkMeta);
			
			// handle the form events
			jq('form.field-options-form button#apply').click(function() {
				var title = $('Form_EditForm_Title').value;
				
				if (urlSegmentField && jq('#boxMatchURL').is(':checked')) {
					urlSegmentField.value = title;
				}
				
				if ( (menutitle = $('Form_EditForm_MenuTitle')) && jq('#boxMatchMenu').is(':checked')) {
					menutitle.value = title; 
				}
				
				if ( (metatitle = $('Form_EditForm_MetaTitle')) && jq('#boxMatchMeta').is(':checked')) {
					metatitle.value = title;
				}
				
				hideFieldOptions();
			});
			
			jq('form.field-options-form button#cancel').click(function() {
				hideFieldOptions();
			});
				
		}
	}
});

function hideFieldOptions() {
	// Remove the form gracefuly from the Title container with a hide animation -> remove.
	jq('.field-options').hide(500, function() { jq('.field-options').remove(); });
}

function updateFieldsPrompt(showURLSegment, showMenuTitle, showMetaTitle) {
	// Insert the form into the Title container (only if it's not already there)
	if(jq('.field-options').length != 0) 
		return;
	
	var codez = ' \
		<div class="field-options"> \
			<form class="field-options-form" action=""> \
			<label class="left">Update fields to match page name:</label> ';
			
	if (showURLSegment) 
		codez += '<input type="checkbox" id="boxMatchURL" value="url" /><label for="boxMatchURL">URL</label>';
		
	if (showMenuTitle) 
		codez += '<input type="checkbox" id="boxMatchMenu" value="menu" /><label for="boxMatchMenu">Menu Title</label>';
		
	if (showMetaTitle) 
		codez += '<input type="checkbox" id="boxMatchMeta" value="meta" /><label for="boxMatchMeta">SEO Meta Title</label>';
				
			
	codez += '<button id="apply" type="button">Apply</button>&nbsp;<button id="cancel" type="button">Cancel</button> \
			</form>\
		</div>';
	
	jq('#Title').append(codez);
	
	jq('.field-options').show(500);
}