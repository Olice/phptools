/**
 * Translations
 * loads a translation XML file into an HTML document using data-text-id attributes
 * for each content tag (P, H1-6, LI)
 * this is for interactive popups whose translation is not handled by the top level player
 *
 * Modification history:
 *
 * 2014-07-08: Use jQuery selectors to locate ID and html content within XML file
 *                       for better reliability
 *
 * 2014-07-08: Read in a single content.xml instead of multiple for each page
 *
 * 2014-07-09: Now include the folder name in the page ID so that we can support interactives
 *                       eg, for pages the ID in the XML file is still page1-1-1
 *                       for interactives it is now page1-1-1-interactive1
 * 
 * 2014-11-25: Now replaces values on INPUT tags 
 */
$(function() {
    // Create an IE friendly version of console.log if there is none
    if (!window.console) console = {
        log: function() {}
    };

    // The current HTML filename
    var htmlFile = location.pathname.substring(location.pathname.lastIndexOf("/") + 1);

    // The location of the XML file
    var xmlFile = '../content.xml';

    // Check if we're in an interactive etc subfolder
    if (htmlFile.match(/^page/) == null) {
        xmlFile = '../' + xmlFile;
    }

    // The ID to search from in the XML file
    //var pageId = htmlFile.replace('.html', '');

    // Are we in an interactive?  If so put the page folder before
    //if (pageId.substr(0, 4) != 'page') {
        var index = location.href.indexOf('page');
        var parts = location.href.substr(index).replace('.html', '').split('/');

        var pageId = parts[0] + '_' + (parts[2] ? (parts[2] + '_' + parts[2]) : parts[1]);
    //}
    console.log(pageId);

    $.ajax({
        type: "GET",
        url: xmlFile,
        dataType: "xml",
        success: function(xml) {

            var $root = $(xml).find('#' + pageId);

            tags = $root.find("text");

            for (i = 0; i < tags.length; i++) {

                var id = $(tags[i]).attr('id');
                var text = $(tags[i]).text();

                //$('[data-text-id=' + id + ']').html(text);

                $('[data-text-id=' + id + ']').each(function(i, element) {
                    switch(element.tagName) {

                        case 'INPUT':
                            $(element).val(text);
                            break;

                        default:
                            $(element).html(text);
                    }
                });


            }
        }
    });

});
