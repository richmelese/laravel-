<div class="container">
    <div class="bc_hotel-header bc_header--info">
        <div class="info__content">
            <div class="left">
                <a class="bc_header--info__link">{{$translation->address}}</a>
                <h1 class="bc_title">{{$translation->title}}</h1>
            </div>
            <div class="right">
                <div class="shares dropdown">
                    <div class="shares__wishlist">
                        @if(method_exists($row, 'isWishList'))
                            <div class="share-item like-it">
                                <div class="service-wishlist {{$row->isWishList()}}" data-id="{{$row->id}}" data-type="{{$row->type}}">
                                    <i class="fa fa-heart-o"></i>
                                </div>
                            </div>
                        @endif
                    </div>
                    <ul class="share-wrapper">
                        <li class="share-bg--facebook">
                            <a class="facebook" href="https://www.facebook.com/sharer/sharer.php?u={{$row->getDetailUrl()}}&amp;title={{$translation->title}}" target="_blank" rel="noopener">
                                <i class="fa fa-facebook fa-lg"></i>
                            </a>
                        </li>
                        <li class="share-bg--twitter">
                            <a class="twitter" href="https://twitter.com/share?url={{$row->getDetailUrl()}}&amp;title={{$translation->title}}" target="_blank" rel="noopener">
                                <i class="fa fa-twitter fa-lg"></i>
                            </a>
                        </li>
                        <li class="share-bg--pinterest">
                            <a class="no-open pinterest" href="http://pinterest.com/pin/create/bookmarklet/?url={{$row->getDetailUrl()}}&amp;description={{$translation->title}}" target="_blank" rel="noopener">
                                <i class="fa fa-pinterest fa-lg"></i>
                            </a>
                        </li>
                        <li class="share-bg--linkedin">
                            <a class="linkedin" href="https://www.linkedin.com/shareArticle?mini=true&amp;url={{$row->getDetailUrl()}}&amp;title={{$translation->title}}" target="_blank" rel="noopener">
                                <i class="fa fa-linkedin fa-lg"></i>
                            </a>
                        </li>
                    </ul>
                    <div class="shares__social">
                        <a href="#" class="share-item social-share">
                            <i class="input-icon bc_border-radius field-icon fa">
                                <svg width="24px" height="24px" viewBox="0 0 512 512">
                                    <path fill="white" d="M503.691 189.836L327.687 37.851C312.281 24.546 288 35.347 288 56.015v80.053C127.371 137.907 0 170.1 0 322.326c0 61.441 39.581 122.309 83.333 154.132 13.653 9.931 33.111-2.533 28.077-18.631C66.066 312.814 132.917 274.316 288 272.085V360c0 20.7 24.3 31.453 39.687 18.164l176.004-152c11.071-9.562 11.086-26.753 0-36.328z"></path>
                                </svg>
                            </i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="bc_permalink">
            <ul class="bc_permalink--item">
                <li class="item__color">
                    <a href="/">
                        {{ __("Home") }}
                    </a>
                </li>
                <li>
                    {{$translation->title}}
                </li>
            </ul>
        </div>
    </div>
</div>
