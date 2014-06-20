$(document).ready(function() {
	var editor = ace.edit('editor');
	editor.setTheme('ace/theme/twilight');
	editor.getSession().setMode('ace/mode/markdown');
	editor.setShowInvisibles(true);

	$('#post-form').submit(function() {
		$('#Post_text').val(editor.getValue());
	});
});
