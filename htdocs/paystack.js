document.addEventListener('DOMContentLoaded', function() {
    let paymentCompleted = false; // Track payment status
    
    document.getElementById('payButton').addEventListener('click', function(e) {
        e.preventDefault();
        
        // Get form values
        const operator = document.getElementById('summary-operator').innerText;
        const phone = document.getElementById('phone').value;
        const email = document.getElementById('email').value;
        const packageText = document.getElementById('summary-package').innerText;
        const totalPrice = parseFloat(document.getElementById('total-price').innerText);

        // Convert amount to subunits
        const amount = totalPrice * 100;

        // Initialize Paystack
        const handler = PaystackPop.setup({
            key: 'pk_live_ea0c87747238f0f71e5ce276338f612c4a74f071', // Test key pk_test_1828fb257fb0cfd762bd2507e089bd367e4a4e3e
            email: email,
            amount: amount,
            currency: 'GHS',
            metadata: {
                custom_fields: [
                    {
                        display_name: "Network Operator",
                        variable_name: "network_operator",
                        value: operator
                    },
                    {
                        display_name: "Mobile Number",
                        variable_name: "phone",
                        value: phone
                    },
                    {
                        display_name: "Data Package",
                        variable_name: "package",
                        value: packageText
                    },
                ]
            },
            onClose: function() {
                if (!paymentCompleted) {
                    alert('Payment was not completed. Please try again.');
                }
            },
            callback: function(response) {
                paymentCompleted = true;
                // Directly redirect to success page
                window.location.href = 'https://datapadi.shop/order-success.html';
            }
        });
        
        handler.openIframe();
    });
});