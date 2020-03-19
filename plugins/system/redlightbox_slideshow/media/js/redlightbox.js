function preloadSlimbox(isenable)
{
    (function($){
        $(document).ready(function(){
            $("a[rel^=\'myallimg\']").attr("rel","lightbox[gallery]");
            if (!/android|iphone|ipod|series60|symbian|windows ce|blackberry/i.test(navigator.userAgent))
            {
                $("a[rel^=\'lightbox\']").slimbox(isenable, null, function(el) {
                    return (this == el) || ((this.rel.length > 8) && (this.rel == el.rel));
                });
            }
            else {
                const pswp = `
                    <div class="pswp" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="pswp__bg"></div>
                        <div class="pswp__scroll-wrap">
                            <div class="pswp__container">
                                <div class="pswp__item"></div>
                                <div class="pswp__item"></div>
                                <div class="pswp__item"></div>
                            </div>
                            <div class="pswp__ui pswp__ui--hidden">
                                <div class="pswp__top-bar">
                                    <div class="pswp__counter"></div>
                                    <button class="pswp__button pswp__button--close" title="Close (Esc)"></button>
                                    <button class="pswp__button pswp__button--share" title="Share"></button>
                                    <button class="pswp__button pswp__button--fs" title="Toggle fullscreen"></button>
                                    <button class="pswp__button pswp__button--zoom" title="Zoom in/out"></button>
                                    <div class="pswp__preloader">
                                        <div class="pswp__preloader__icn">
                                            <div class="pswp__preloader__cut">
                                                <div class="pswp__preloader__donut"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="pswp__share-modal pswp__share-modal--hidden pswp__single-tap">
                                    <div class="pswp__share-tooltip"></div>
                                </div>
                                <button class="pswp__button pswp__button--arrow--left" title="Previous (arrow left)">
                                </button>
                                <button class="pswp__button pswp__button--arrow--right" title="Next (arrow right)">
                                </button>
                                <div class="pswp__caption">
                                    <div class="pswp__caption__center"></div>
                                </div>
                            </div>
                        </div>
                    </div>`;
                $('.product_image').append(pswp);
                var test = $("a[rel^=\'lightbox\']");
                var openPhotoWipe = function() {
                    var items = [];

                    $.each(test, function (index, value) {
                        items.push({
                            src: $(this).attr('href').toString(),
                            w: 964,
                            h: 1024
                        });
                    });
                    // define options (if needed)
                    var options = {
                        // history & focus options are disabled on CodePen
                        history: false,
                        focus: false,
                        showAnimationDuration: 0,
                        hideAnimationDuration: 0
                    };
                    var pswpElement = document.querySelectorAll('.pswp')[0];
                    var gallery = new PhotoSwipe(pswpElement, PhotoSwipeUI_Default, items);
                    gallery.init();
                }

                $.each(test, function (index, value) {
                    $(this).click(function (e) {
                        e.preventDefault();
                        openPhotoWipe();
                    });
                });
            }
        });
    })(jQuery);
}