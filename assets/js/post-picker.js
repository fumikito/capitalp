!function(e){"use strict";e(document).ready(function(){e(".cappy-post-picker").select2({placeholder:"オリジナルがある場合は選択",ajax:{url:wpApiSettings.root+"wp/v2/posts",dataType:"json",delay:250,data:function(e){return{search:e.term,orderby:"title",order:"asc",_wpnonce:wpApiSettings.nonce}},processResults:function(t,n){return console.log(t,n),n.page=n.page||1,{results:e.map(t,function(e){return{id:e.id,text:e.title.rendered}}),pagination:{more:30*n.page<t.total_count}}},cache:!0},escapeMarkup:function(e){return e},minimumInputLength:1})})}(jQuery);
//# sourceMappingURL=map/post-picker.js.map
