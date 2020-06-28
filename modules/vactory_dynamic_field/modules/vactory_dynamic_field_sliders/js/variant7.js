"use strict";

function _instanceof(left, right) { if (right != null && typeof Symbol !== "undefined" && right[Symbol.hasInstance]) { return !!right[Symbol.hasInstance](left); } else { return left instanceof right; } }

function _classCallCheck(instance, Constructor) { if (!_instanceof(instance, Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

// The Slideshow class.
var Slideshow =
  /*#__PURE__*/
  function () {
    function Slideshow(el) {
      _classCallCheck(this, Slideshow);

      this.DOM = {
        el: el
      };
      this.config = {
        slideshow: {
          delay: 3000,
          pagination: {
            duration: 3
          }
        }
      }; // Set the slideshow

      this.init();
    }

    _createClass(Slideshow, [{
      key: "init",
      value: function init() {
        var self = this; // Charmed title

        this.DOM.slideTitle = this.DOM.el.querySelectorAll('.slide-title');
        this.DOM.slideTitle.forEach(function (slideTitle) {//charming(slideTitle);
        }); // Set the slider

        this.slideshow = new Swiper(this.DOM.el, {
          loop: true,
          /*autoplay: {
            delay: this.config.slideshow.delay,
            disableOnInteraction: false
          },*/
          speed: 500,
          preloadImages: true,
          updateOnImagesReady: true,
          // lazy: true,
          // preloadImages: false,
          pagination: {
            el: '.slideshow-pagination',
            clickable: true,
            bulletClass: 'slideshow-pagination-item',
            bulletActiveClass: 'active',
            clickableClass: 'slideshow-pagination-clickable',
            modifierClass: 'slideshow-pagination-',
            renderBullet: function renderBullet(index, className) {
              var slideIndex = index,
                number = index <= 8 ? '0' + (slideIndex + 1) : slideIndex + 1;
              var paginationItem = '<span class="slideshow-pagination-item">';
              paginationItem += '<span class="pagination-number">' + number + '</span>';
              paginationItem = index <= 8 ? paginationItem + '<span class="pagination-separator"><span class="pagination-separator-loader"></span></span>' : paginationItem;
              paginationItem += '</span>';
              return paginationItem;
            }
          },
          // Navigation arrows
          navigation: {
            nextEl: '.slideshow-navigation-button.next',
            prevEl: '.slideshow-navigation-button.prev'
          },
          // And if we need scrollbar
          scrollbar: {
            el: '.swiper-scrollbar'
          },
          on: {
            init: function init() {
              self.animate('next');
            }
          }
        }); // Init/Bind events.

        this.initEvents();
      }
    }, {
      key: "initEvents",
      value: function initEvents() {
        var _this = this;

        this.slideshow.on('paginationUpdate', function (swiper, paginationEl) {
          return _this.animatePagination(swiper, paginationEl);
        }); //this.slideshow.on('paginationRender', (swiper, paginationEl) => this.animatePagination());

        this.slideshow.on('slideNextTransitionStart', function () {
          return _this.animate('next');
        });
        this.slideshow.on('slidePrevTransitionStart', function () {
          return _this.animate('prev');
        });
      }
    }, {
      key: "animate",
      value: function animate() {
        var _this2 = this;

        var direction = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'next';
        // Get the active slide
        this.DOM.activeSlide = this.DOM.el.querySelector('.swiper-slide-active'),
          this.DOM.activeSlideImg = this.DOM.activeSlide.querySelector('.slide-image'),
          this.DOM.activeSlideTitle = this.DOM.activeSlide.querySelector('.slide-title'),
          this.DOM.activeSlideTitleLetters = this.DOM.activeSlideTitle.querySelectorAll('span.vertical-part'); // Reverse if prev

        this.DOM.activeSlideTitleLetters = direction === "next" ? this.DOM.activeSlideTitleLetters : [].slice.call(this.DOM.activeSlideTitleLetters).reverse(); // Get old slide

        this.DOM.oldSlide = direction === "next" ? this.DOM.el.querySelector('.swiper-slide-prev') : this.DOM.el.querySelector('.swiper-slide-next');

        if (this.DOM.oldSlide) {
          // Get parts
          this.DOM.oldSlideTitle = this.DOM.oldSlide.querySelector('.slide-title'),
            this.DOM.oldSlideTitleLetters = this.DOM.oldSlideTitle.querySelectorAll('span.vertical-part'); // Animate

          this.DOM.oldSlideTitleLetters.forEach(function (letter, pos) {
            TweenMax.to(letter, 0, {
              ease: Quart.easeIn,
              delay: 0,
              y: '50%',
              opacity: 0
            });
          });
        } // Animate title


        TweenMax.to(this.DOM.activeSlideTitleLetters, 0, {
          y: "50%",
          opacity: 0,
        })

        var $target = this.DOM.activeSlideTitleLetters;
        $target.forEach(function(letter, pos) {
          letter.removeAttribute("style");
        })
        console.log('------------------------------------');
        var _timer = setTimeout(function () {
          var pos = 0;
          $target.forEach(function (letter, pos) {
            console.log(letter);
            TweenMax.to(letter, .6, {
              ease: Back.easeOut,
              delay: pos * .05,
              startAt: {
                y: '50%',
                opacity: 0
              },
              y: '0%',
              opacity: 1
            });
          }); // Animate background
          clearTimeout(_timer);
        }, 100)
        console.log('------------------------------------');

        TweenMax.to(this.DOM.activeSlideImg, 1.5, {
          ease: Expo.easeOut,
          startAt: {
            x: direction === 'next' ? 200 : -200
          },
          x: 0
        }); //this.animatePagination()
      }
    }, {
      key: "animatePagination",
      value: function animatePagination(swiper, paginationEl) {
        // Animate pagination
        this.DOM.paginationItemsLoader = paginationEl.querySelectorAll('.pagination-separator-loader');
        this.DOM.activePaginationItem = paginationEl.querySelector('.slideshow-pagination-item.active');
        this.DOM.activePaginationItemLoader = this.DOM.activePaginationItem.querySelector('.pagination-separator-loader'); //console.log(swiper.pagination);
        // console.log(swiper.activeIndex);
        // Reset and animate

        TweenMax.set(this.DOM.paginationItemsLoader, {
          scaleX: 0
        });
        TweenMax.to(this.DOM.activePaginationItemLoader, this.config.slideshow.pagination.duration, {
          startAt: {
            scaleX: 0
          },
          scaleX: 1
        });
      }
    }]);

    return Slideshow;
  }();

var slideshow = new Slideshow(document.querySelector('.slideshow'));
