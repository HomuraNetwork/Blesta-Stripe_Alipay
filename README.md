# Stripe Alipay

This is a non-merchant gateway for Blesta in order to support Alipay via Stripe. 

这是用于 Blesta 的非商户网关，以支持通过Stripe使用支付宝收款。

> [!CAUTION]
> **NOTE: This gateway has not been tested or reviewed for code accuracy. Use it at your own risk!**
> 
> **注意：此网关尚未经过测试或代码准确性审查。风险自负！**

Alipay is a digital wallet based in China. This payment gateway enables you to accept Alipay payments through Stripe, enhancing the experience for Chinese customers.

支付宝是一款中国的数字钱包。该支付网关使您通过Stripe接受支付宝付款，以增强中国用户的体验。


For more information, please visit [Stripe - Alipay: An in-depth guide](https://stripe.com/resources/more/alipay-an-in-depth-guide).

有关更多信息，请访问[Stripe - Alipay: An in-depth guide](https://stripe.com/resources/more/alipay-an-in-depth-guide)。


## How to use - 如何使用

1.  Upload the source code to the `/components/gateways/nonmerchant/stripe_alipay/` directory in your Blesta installation path.
2.  Log into your Blesta admin panel and navigate to `Settings > Payment Gateways > Available`.
3.  Click the `Install` button to activate it.
4.  Follow the instructions on that page, create a webhook with `payment_intent.succeeded`, `payment_intent.canceled`, and another `payment_intent.succeeded` events on your Stripe account. 
5.  Input your Secret Key and WebHook Secret, then choose the currency in which you can accept Alipay payments.

----

1. 将源代码上传到您的Blesta安装路径中的`/components/gateways/nonmerchant/stripe_alipay/`目录。
2. 登录到您的Blesta管理员面板，并导航至 `Settings > Payment Gateways > Available`。
3. 点击 `Install` 按钮进行激活。
4. 根据该页面上的说明，在你的Stripe账户上创建一个带有 `payment_intent.succeeded`, `payment_intent.canceled`, 和另一个 `payment_intent.succeeded` 事件的webhook.
5. 输入你的密钥和WebHook秘密，然后选择可以接受支付宝付款的货币类型。

> [!TIP]
> If you're uncertain your currency setting, enter the currency code and secret key on the settings page. Then, select the 'Test' checkbox and attempt to save your settings.
> 
> 如果您不确定币种配置是否正确，可以在设置页面输入货币代码和密钥，然后选中 'Test' 复选框并尝试保存你的设置。
>
> You can access debugging information through the Stripe Developer Dashboard.
>
> 您可以通过Stripe开发者仪表板访问调试信息。
>
> [Stripe Docs - Developers Dashboard](https://docs.stripe.com/development/dashboard)



## Functions and Tasks

- [x] Implement payment processing through Alipay

- [x] Enable refunds via the admin panel 

- [x] Establish a process fee for payments or refunds 

  - [x] Provide support for fixed fees 

  - [x] Incorporate percentage-based fees 

- [ ] Implement currency conversion feature

- [ ] Improve code quality and logic

> 
>[!TIP]
>If you intend to have your customer cover the transaction fee, ensure that it is clearly and accurately stated. You can modify this in `language/en_us/stripe_alipay.php` and also change the name of this plugin if necessary.
>
>如果你计划让你的客户支付交易费用，一定要清楚准确地说明。你可以修改`language/en_us/stripe_alipay.php`并在必要时更改此插件的名称。

>[!WARNING]
>If you add a percentage-based payment fee when the user makes a payment, there may be an error with amount in the refund function. This plugin calculates based on the amount that needs to be paid, whereas Stripe calculates based on the actual amount paid.
>
>如果你在用户付款时添加了基于百分比的支付费用，那么在退款功能中可能会出现金额错误。这个插件是根据需要支付的金额进行计算的，而Stripe则是根据实际支付的金额进行计算。

## Requirements:
- [Blesta](https://www.blesta.com/)
- PHP 7+
- [Stripe PHP Library (Included)](https://github.com/stripe/stripe-php/)

## Thanks to 
[wirecatllc/blesta-stripe-universal](https://github.com/wirecatllc/blesta-stripe-universal)

## License
MIT

-----------------------------------

Copyright (c) [Homura Network Limited - 吼姆拉網絡有限公司](https://homura.network)