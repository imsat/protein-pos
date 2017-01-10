@extends('layouts.app')

@section('title')
    - Create Sales
@endsection

@section('content')
    @parent
    <div class="row" id="app" v-cloak>
        <div class="col-md-7">
            <div class="panel panel-default">
                <div class="panel-body" id="search-product-panel">
                    <search-product src="{{ route('products.xhr.search') }}" :existing-items="cart" v-on:product-selected="addToCart($event.product, 1)"></search-product>
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-body" id="products-panel">
                    <div v-show="isCartEmpty">
                        <span class="label label-primary">No items on cart</span>
                    </div>
                    <table class="table table-hover" v-show="!isCartEmpty">
                        <thead>
                            <tr class="register-items-header">
                                <th class="text-center"></th>
                                <th class="text-center">Item Name</th>
                                <th class="text-center">Price</th>
                                <th class="text-center">Qty. / Max</th>
                                <th class="text-center">Disc %</th>
                                <th class="text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(cartItem, index) in cart">
                                <td>
                                    <a class="btn btn-xs text-danger" v-on:click="removeFromCart(index)">
                                        <i class="fa fa-times-circle"></i>
                                    </a>
                                </td>
                                <td>@{{ cartItem.product.name }}</td>
                                <td>@{{ cartItem.product.price }}</td>
                                <td style="width: 100px;">
                                    <input type="text" class="form-control" v-model="cartItem.quantity" /> / @{{ cartItem.product.availableQuantity }}
                                </td>
                                <td style="width: 70px;">
                                    <input type="text" class="form-control" v-model="cartItem.discount" />
                                </td>
                                <td>@{{ calculateItemPrice(cartItem) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="panel panel-default">
                <div class="panel-body" id="search-customer-panel">
                    <search-customer src="{{ route('customers.xhr.search') }}" v-on:customer-selected="setCustomer($event.customer)" v-show="!isCustomerSelected"></search-customer>
                    <div class="customer-info" v-show="isCustomerSelected">
                        <div class="row">
                            <div class="col-xs-12">
                                <h4 class="name">
                                    @{{ customer.name }}
                                    <span class="label label-success" v-show="customer.group">
                                        <i class="fa fa-star"></i>
                                        @{{ customer.groupLabel }}
                                    </span>
                                </h4>
                                <div class="screen-name">
                                    <i class="fa fa-phone"></i> @{{ customer.phone || "-" }} &nbsp; <i class="fa fa-envelope"></i> @{{ customer.email }}
                                </div>
                            </div>
                        </div>
                        <br/>
                        <div class="row">
                            <div class="col-xs-6">
                                <button class="btn btn-primary btn-block">
                                    <i class="fa fa-search-plus"></i>
                                    Show details
                                </button>
                            </div>
                            <div class="col-xs-6">
                                <button class="btn btn-default btn-block" v-on:click="setCustomer({})">
                                    <i class="fa fa-times"></i>
                                    Change customer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-body" id="sales-summary-panel">
                    <div class="form-horizontal">
                        <div class="form-group">
                            <label class="control-label col-xs-4 text-left">Customer Discount</label>
                            <div class="col-xs-6">
                                <p class="form-control-static text-primary">
                                    <strong>@{{ customer.group ? customer.group.discount + "%" : "-" }}</strong>
                                </p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-xs-4 text-left">Sales Discount</label>
                            <div class="col-xs-3">
                                <div class="input-group">
                                    <input type="text" class="form-control" v-model="salesDiscount" />
                                    <span class="input-group-addon">%</span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-xs-4 text-left">Subtotal</label>
                            <div class="col-xs-3">
                                <p class="form-control-static text-primary">
                                    <strong>@{{ subTotal }}</strong>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @parent
    <script type="text/javascript">
        const app = new Vue({
            el: "#app",
            data: {
                query: "",
                salesDiscount: 0,
                cart: [],
                customer: {}
            },
            computed: {
                isCartEmpty: function () { return this.cart.length === 0; },
                isCustomerSelected: function () { return this.customer.hasOwnProperty('id'); },
                subTotal: function () {
                    var itemsTotal = 0,
                        $this = this;

                    this.cart.forEach(function (cartItem) {
                        itemsTotal += $this.calculateItemPrice(cartItem);
                    });

                    if ($this.customer.group) {
                        itemsTotal = $this.applyDiscount(itemsTotal, this.customer.group.discount);
                    }

                    itemsTotal = $this.applyDiscount(itemsTotal, this.salesDiscount);

                    return itemsTotal;
                }
            },
            methods: {
                applyDiscount: function(original, discount) { return original * (100 - discount) / 100; },
                calculateItemPrice: function (item) { return this.applyDiscount(item.product.price * item.quantity, item.discount); },
                setCustomer: function (customer) { this.customer = customer; },
                addToCart: function (product, quantity) {
                    var sameProduct = false;

                    this.cart.forEach(function (cartItem) {
                        if (cartItem.product.id === product.id) {
                            cartItem.quantity += quantity;
                            sameProduct = true;
                        }
                    });

                    if (!sameProduct) {
                        this.cart.push({
                            product: product,
                            quantity: quantity,
                            discount: 0
                        })
                    }
                },
                removeFromCart: function (index) {
                    this.cart.splice(index, 1);
                },
                findByBarcode: function(query) {
                    var $this = this;

                    $.get("{{ url('/products/xhr-search') }}", {
                        query: query
                    }, function (response) {
                        $this.addToCart(response.product, 1);
                        $this.query = "";
                    });
                }
            }
        });

        $(document).ready(function () {
            $(window).on("paste", function (e) {
                if ($(e.target).attr("id") === $queryBox.attr("id")) {
                    setTimeout(function () {
                        app.findByBarcode($queryBox.val());
                    }, 100);
                }
            });
        });
    </script>
@endsection