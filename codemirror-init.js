jQuery(document).ready(function ($) {
    // Fetch the saved template content from localized data
    var savedTemplate = codemirror_params.default_template_content;

    // Default HTML template
    var defaultTemplate = `<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>POST_TITLE</title>
    </head>
    <body>
    
        <!-- Post content goes here -->
    
    </body>
    </html>`;

    // If there's a saved template, use it; otherwise, use the default template
    var templateToUse = savedTemplate || defaultTemplate;

    // Initialize CodeMirror with the selected template
    var editor = CodeMirror.fromTextArea(document.getElementById('html_template_content'), {
        mode: 'htmlmixed',
        lineNumbers: true,
        autoCloseBrackets: true,
        matchBrackets: true,
        search: true,
    });

    editor.setValue(templateToUse);

    // Message container 
    var messageContainer = $('<div class="notice notice-success is-dismissible"><p></p></div>').insertAfter('#html_template_content').hide();

    // Save template
    $("form").submit(function (event) {
        event.preventDefault();
        var templateContent = editor.getValue();
        $.ajax({
            type: "POST",
            url: ajaxurl,
            data: {
                action: "save_html_template",
                html_template_content: templateContent
            },
            success: function (response) {
                // Display success message
                messageContainer.find('p').text('Template saved successfully!');
                messageContainer.show();
            }
        });
    });
});
