!function(e){var n={};function t(o){if(n[o])return n[o].exports;var i=n[o]={i:o,l:!1,exports:{}};return e[o].call(i.exports,i,i.exports,t),i.l=!0,i.exports}t.m=e,t.c=n,t.d=function(e,n,o){t.o(e,n)||Object.defineProperty(e,n,{enumerable:!0,get:o})},t.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},t.t=function(e,n){if(1&n&&(e=t(e)),8&n)return e;if(4&n&&"object"==typeof e&&e&&e.__esModule)return e;var o=Object.create(null);if(t.r(o),Object.defineProperty(o,"default",{enumerable:!0,value:e}),2&n&&"string"!=typeof e)for(var i in e)t.d(o,i,function(n){return e[n]}.bind(null,i));return o},t.n=function(e){var n=e&&e.__esModule?function(){return e.default}:function(){return e};return t.d(n,"a",n),n},t.o=function(e,n){return Object.prototype.hasOwnProperty.call(e,n)},t.p="",t(t.s=0)}([function(e,n,t){e.exports=t(1)},function(e,n){var t=jQuery,o=wp.i18n,i=o.__,r=o.sprintf;function a(){var e=t(".ofuse-instruction");e.addClass("loading"),CookieTasting.testBefore().then(function(e){return wp.apiFetch({path:"/chiramise/v1/content/".concat(CapitalpContents.postId),method:"POST"})}).then(function(n){t(".gumroad-product-embed, .gumroad-embed-iframe").remove();var o=i("Thank you for supporting us!","capitalp"),a='\n      <div class="ofuse-more">\n        <i></i> '.concat(o,"\n      </div>\n    "),u='\n      <div class="ofuse-success">\n          <p>'+r(i("Howdy, %s!","capitalp"),CookieTasting.userName())+"</p>\n          <ol>\n            <li>"+r(i("To get premium files, please go to %s and login. In your library, you can find Capital P resources.","capitalp"),'<a href="https://gumroad.com/library" target="_blank"></a>')+"</li>\n            <li>"+r(i('You can manage and check your license in <a href="%s">profile page</a>.',"capitalp"),"/wp-admin/profile.php")+"</li>\n          </ol>\n      </div> \n    ";e.before(a).after(u).replaceWith(n.content)}).catch(function(n){var t='\n        <div class="u-text-center">\n          <p class="ofuse-instruction-text u-text-center">%s</p>\n          <p>%s</p>\n          <a class="ofuse-button" href="/wp-admin/profile.php">%s</a>\n        </div>\n    ';t=r(t,i("License Required!","capitalp"),i("To read full contents, buy new license at gumroad and save it on your profile page.","capitalp"),i("Save License at Profile Page","capitalp")),e.removeClass("loading").html(t)}).finally(function(e){t(".p-entry-content").effect("highlight")})}t(document).ready(function(){CookieTasting.isLoggedIn()&&a(),t(".ofuse-login .ofuse-button").click(function(e){e.preventDefault();var n=t(this).attr("href");CookieTasting.testBefore().then(function(e){a()}).catch(function(e){window.location.href=n}).finally(function(e){})})})}]);
//# sourceMappingURL=capitalp-contents.js.map