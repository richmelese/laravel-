<div class="bc_single_book_wrap">
    <div class="bc_single_book">
        <div id="bc_car_book_app" v-cloak>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($row->discount_percent): ?>
                <div class="tour-sale-box">
                    <span class="sale_class box_sale sale_small"><?php echo e($row->discount_percent); ?></span>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <div class="form-head">
                <div class="price">
                    <span class="label">
                        <?php echo e(__('from')); ?>

                    </span>
                    <span class="value">
                        <span class="onsale"><?php echo e($row->display_sale_price); ?></span>
                        <span class="text-lg"><?php echo e($row->display_price); ?></span>
                    </span>
                </div>
            </div>
            <div class="nav-enquiry" v-if="is_form_enquiry_and_book">
                <div class="enquiry-item active">
                    <span><?php echo e(__('Book')); ?></span>
                </div>
                <div class="enquiry-item" data-toggle="modal" data-target="#enquiry_form_modal">
                    <span><?php echo e(__('Enquiry')); ?></span>
                </div>
            </div>
            <div class="form-book" v-bind:class="{ 'd-none': enquiry_type != 'book' }">
                <div class="form-content">
                    <div class="form-group form-date-field form-date-search clearfix "
                        data-format="<?php echo e(get_moment_date_format()); ?>">
                        <div class="form-section-group form-group">
                            <label><?php echo e(__('Pick up location')); ?></label>
                            <div class="form-group ">
                                <input type="text" class="form-control" v-model="pick_up"
                                    placeholder="<?php echo e(__('Type address')); ?>">
                            </div>
                            <label><?php echo e(__('Drop off location')); ?></label>
                            <div class="form-group ">
                                <input type="text" class="form-control" v-model="drop_off"
                                    placeholder="<?php echo e(__('Type address')); ?>">
                            </div>
                        </div>
                        <div class="date-wrapper clearfix" @click="openStartDate">
                            <div class="check-in-wrapper">
                                <label><?php echo e(__('Select Dates')); ?></label>
                                <div class="render check-in-render" v-html="start_date_html"></div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($row->min_day_before_booking)): ?>
                                    <div class="render check-in-render">
                                        <small>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($row->min_day_before_booking > 1): ?>
                                                -
                                                <?php echo e(__('Book :number days in advance', ['number' => $row->min_day_before_booking])); ?>

                                            <?php else: ?>
                                                -
                                                <?php echo e(__('Book :number day in advance', ['number' => $row->min_day_before_booking])); ?>

                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </small>
                                    </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($row->min_day_stays)): ?>
                                    <div class="render check-in-render">
                                        <small>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($row->min_day_stays > 1): ?>
                                                -
                                                <?php echo e(__('Stay at least :number days', ['number' => $row->min_day_stays])); ?>

                                            <?php else: ?>
                                                - <?php echo e(__('Stay at least :number day', ['number' => $row->min_day_stays])); ?>

                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </small>
                                    </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                            <i class="fa fa-angle-down arrow"></i>
                        </div>
                        <input type="text" class="start_date" ref="start_date"
                            style="height: 1px; visibility: hidden">
                    </div>
                    <div class="form-group form-guest-search">
                        <div class="guest-wrapper d-flex justify-content-between align-items-center">
                            <div class="flex-grow-1">
                                <label><?php echo e(__('Number')); ?></label>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="input-number-group">
                                    <i class="icon ion-ios-remove-circle-outline" @click="minusNumberType()"></i>
                                    <span class="input"><input type="number" v-model="number" min="0" /></span>
                                    <i class="icon ion-ios-add-circle-outline" @click="addNumberType()"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-section-group form-group" v-if="extra_price.length">
                        <h4 class="form-section-title"><?php echo e(__('Extra prices:')); ?></h4>
                        <div class="form-group " v-for="(type,index) in extra_price">
                            <div class="extra-price-wrap d-flex justify-content-between">
                                <div class="flex-grow-1">
                                    <label><input type="checkbox" true-value="1" false-value="0" v-model="type.enable">
                                        {{ type.name }}</label>
                                    <div class="render" v-if="type.price_type">({{ type.price_type }})</div>
                                </div>
                                <div class="flex-shrink-0">{{ type.price_html }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-section-group form-group-padding" v-if="buyer_fees.length">
                        <div class="extra-price-wrap d-flex justify-content-between" v-for="(type,index) in buyer_fees">
                            <div class="flex-grow-1">
                                <label>{{ type.type_name }}
                                    <i class="icofont-info-circle" v-if="type.desc" data-toggle="tooltip"
                                        data-placement="top" v-bind:title="type.type_desc"></i>
                                </label>
                                <div class="render" v-if="type.price_type">({{ type.price_type }})</div>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="unit" v-if='type.unit == "percent"'>
                                    {{ type.price }}%
                                </div>
                                <div class="unit" v-else>
                                    {{ formatMoney(type.price) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <ul class="form-section-total list-unstyled" v-if="total_price > 0">
                    <li>
                        <label><?php echo e(__('Total')); ?></label>
                        <span class="price">{{ total_price_html }}</span>
                    </li>
                    <li v-if="is_deposit_ready">
                        <label for=""><?php echo e(__('Pay now')); ?></label>
                        <span class="price">{{ pay_now_price_html }}</span>
                    </li>
                </ul>
                <div v-html="html"></div>
                <div class="submit-group">
                    <a class="btn btn-large" @click="doSubmit($event)"
                        v-bind:class="{ 'disabled': onSubmit, 'btn-success': (step == 2), 'btn-primary': step == 1 }" name="submit">
                        <span v-if="step == 1"><?php echo e(__('BOOK NOW')); ?></span>
                        <span v-if="step == 2"><?php echo e(__('Book Now')); ?></span>
                        <i v-show="onSubmit" class="fa fa-spinner fa-spin"></i>
                    </a>
                    <div class="alert-text mt10" v-show="message.content" v-html="message.content"
                        v-bind:class="{ 'danger': !message.type, 'success': message.type }"></div>
                </div>
            </div>
            <div class="form-send-enquiry" v-show="enquiry_type=='enquiry'">
                <button class="btn btn-primary" data-toggle="modal" data-target="#enquiry_form_modal">
                    <?php echo e(__('Contact Now')); ?>

                </button>
            </div>
        </div>
    </div>
</div>
<?php echo $__env->make('Booking::frontend.global.enquiry-form', ['service_type' => 'car'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php /**PATH C:\Users\richm\Downloads\tonetor-core-booking-system\tonetor-core-v4.0.2\tonetor-core-v4.0.2\bc-cms\themes/BC/Car/Views/frontend/layouts/details/form-book.blade.php ENDPATH**/ ?>