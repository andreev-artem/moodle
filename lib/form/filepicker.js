var pickers = {};
function fp_filepicker_callback(params) {
    var html = '<a href="'+params['url']+'">'+params['file']+'</a>';
    document.getElementById('file_info_'+params['client_id']).innerHTML = html;
}

function fp_init_filepicker(id, options) {
    YUI(M.yui.loader).use("core_filepicker", function (Y) {
        options.formcallback = fp_filepicker_callback;
        if (!pickers[options.client_id]) {
            pickers[options.client_id] = new M.core_filepicker(options); 
        }
        Y.one('#'+id).on('click', function(e, client_id) {
            pickers[options.client_id].show();
        }, this, options.client_id);
    });
}