// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add("login", (email, password) => { ... })
//
//
// -- This is a child command --
// Cypress.Commands.add("drag", { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add("dismiss", { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This is will overwrite an existing command --
// Cypress.Commands.overwrite("visit", (originalFn, url, options) => { ... })
let shortWaitTime = 1000;
let purchaseWaitTime = 2000;
let buttonPressWaitTime = 4000;
let checkoutLoadWaitTime = 16000;

Cypress.Commands.add('visitStore', () => {
    cy.request('http://localhost:4040/api/tunnels').then(
        (request) => {
            //Gets the first element in the lists of URL that includes https to ensure that only the https link is used to avoid conflicts with cypress not wanting two domains.
            const httpsURL = request.body.tunnels.find(element => element.public_url.includes('https://'));
            cy.visit(httpsURL.public_url);
        }
    )
})
Cypress.Commands.add('visitWpAdmin', () => {
    cy.request('http://localhost:4040/api/tunnels').then(
        (request) => {
            //Gets the first element in the lists of URL that includes https to ensure that only the https link is used to avoid conflicts with cypress not wanting two domains.
            const httpsURL = request.body.tunnels.find(element => element.public_url.includes('https://'));
            cy.visit(httpsURL.public_url + '/wp-admin');
        }
    )
})
Cypress.Commands.add('loginWpAdmin', () => {
    cy.visitWpAdmin();
    //Checks if you are logged in or not, if you are not will it log in for you. 
    cy.get("body").then($body => {
        if ($body.find("#user_login").length > 0) {   //evaluates as true
            cy.get('#user_login').should('be.visible').type('admin');
            cy.get('#user_pass').should('be.visible').type('4dm1n');
            cy.get('#wp-submit').click();
            cy.wait(shortWaitTime);
        }
    });
})
Cypress.Commands.add('purchaseArticles', () => {
    let minimumArticlesPurchased = 3; 
    //Presses the button that contains the name products to access the page with all the products. 
    cy.get('.page_item').contains('Products').click();
    //Generate a random number between minimum and 15. 
    let numberOfProductsPurchased = (Math.floor(Math.random() * (15 - minimumArticlesPurchased))) + minimumArticlesPurchased;
    cy.log('Purchasing: ' + numberOfProductsPurchased + ' items.');
    for (var _i = 0; _i < numberOfProductsPurchased; _i++) {
        //Generate a random number between 0 and 13 which is the number of porducts one can buy on the store. . 
        let randomProduct = Math.floor(Math.random() * 13);
        cy.log('Item selected is ID ' + randomProduct.toString());
        //Selects a random product. This item is added to the cart. 
        cy.get('.ajax_add_to_cart').eq(randomProduct).click({ force: true });
        cy.wait(2000);
    }
    //Goes to the billmate checkout page to allow us to start the next step that is to finish the order. 
    cy.get('.page_item').contains('BillmateCheckout').click({ force: true});
})
Cypress.Commands.add('fillOutIframeCompany', (input)=> {
    var user = Cypress.env('user');
    //Accesses iframe and finnishes the order. 
    cy.get('iframe').then(function($iframe){
     const $body = $iframe.contents().find('body');
     cy.wait(purchaseWaitTime);
     
    //Clears the user to make sure the test is the same even if a customer is stored or not and waits to ensure the store has time to process this. 
    cy.wrap($body).find('#aClearCheckout').click({ force: true});

    /*An if-statement made to check if the shopping as a company is stashed. If it is already shopping as a company customer will nothing happen, otherwise will it switch
    customertype.*/
    cy.wait(checkoutLoadWaitTime);
    cy.wrap($body).find('#aToggleCustomerType').invoke('text').then(text=> {
        if (text == 'Handla som företag') {
            cy.wait(buttonPressWaitTime);
            cy.wrap($body).find('#aToggleCustomerType').click({ force: true});
            cy.log('Changed customertype.');
        } else {
            cy.log('Customertype was already company');
        }

    })
    //Types the email found in user.json in the email field.
    cy.wait(buttonPressWaitTime);
    cy.wrap($body).find('#email').type(user.email,{ force: true});
     
    //Types the orgnumber found in the user.json in the pno field. 
    cy.wait(purchaseWaitTime);
    cy.wrap($body).find('#pno').type(user.ORGnumber,{ force: true});

    //continues to the next step
    cy.wait(purchaseWaitTime);
    cy.wrap($body).find('#button-step1').click({ force: true});

    //Fills in the zipcode field.
    cy.wait(buttonPressWaitTime);
    cy.wrap($body).find('#zipcode').type(user.companyZip,{ force: true});

    //continues to the next step
    cy.wait(purchaseWaitTime);
    cy.wrap($body).find('#button-step1').click({ force: true});

    //Fills in the first name of the user.
    cy.wait(buttonPressWaitTime);
    cy.wrap($body).find('#billingAddressAdditionalFirstname').type(user.firstName,{ force: true});
    
    //Fills in the last name of the user.
    cy.wait(buttonPressWaitTime);
    cy.wrap($body).find('#billingAddressAdditionalLastname').type(user.lastName,{ force: true});

    //Continues to the final step of the checkout.
    cy.wait(purchaseWaitTime);
    cy.wrap($body).find('#buttonSubmitBillingAddressAdditional').click({ force: true});
    
    //Selects the payment method sent in. 
    cy.wait(buttonPressWaitTime);
    cy.wrap($body).find('[data-paymentmethod="' + input +'"]').eq(1).click({ force: true});
    
    //Presses the button to finnish the Iframe purchase. 
    cy.wait(buttonPressWaitTime);
    cy.wrap($body).find('.button-step2-enabled').click({ force: true});
    });
     /*Cypress cannot finnish loading the webpage, however despite this is the order finnished properly. To solve this do we reload the page manually which in turn directs
     us to the successURL from where we can get some of the information we need to check.*/
     cy.wait(checkoutLoadWaitTime);
     cy.reload();
     cy.wait(checkoutLoadWaitTime);
})
Cypress.Commands.add('fillOutIframePerson', (input)=> {
       var user = Cypress.env('user');
       //Accesses iframe and finnishes the order. 
       cy.get('iframe').then(function($iframe){

        const $body = $iframe.contents().find('body');
        cy.wait(purchaseWaitTime);

        //Clears the user to make sure the test is the same even if a customer is stored or not and waits to ensure the store has time to process this. 
        cy.wrap($body).find('#aClearCheckout').click({ force: true});
        cy.wait(buttonPressWaitTime)

        /*An if statement made to check if the shopping as a company is stashed. If it is already shopping as a private customer will nothing happen, otherwise will it switch
        customertype.*/
        cy.wrap($body).find('#aToggleCustomerType').invoke('text').then(text=> {
            if (text == 'Handla som privatperson') {
                cy.wait(buttonPressWaitTime);
                cy.wrap($body).find('#aToggleCustomerType').click({ force: true});
            } 
        })

        //Types the email of testperson approved and waits to ensure the store has time to process this.
        cy.wait(buttonPressWaitTime);
        cy.wrap($body).find('#email').type(user.email,{ force: true});

        //Types the swedish "Personnummer" of testperson approved and waits to ensure the store has time to process this. 
        cy.wait(purchaseWaitTime)
        cy.wrap($body).find('#pno').type(user.pno,{ force: true});

        //Presses the "next" button to get to the next step and waits to make sure that the API has loaded the next page.
        cy.wait(shortWaitTime)
        cy.wrap($body).find('#button-step1').click({ force: true});

        //Types the zip code of testperson approved and waits to ensure the store has time to process this. It also uses force because this is sometimes cached and sometimes 
        //not meaning the information is put in irregardless. 
        cy.wait(buttonPressWaitTime);
        cy.wrap($body).find('#zipcode').type(user.zip,{ force: true});
    
        cy.wait(purchaseWaitTime);
        cy.wrap($body).find('.button-step1-enabled').click({ force: true});

        cy.wait(buttonPressWaitTime);
        cy.wrap($body).find('#billingAddressAdditionalPhone').type(user.phoneNumb,{ force: true});

        //Presses button next to get to the final step in the API. 
        cy.wait(purchaseWaitTime);
        cy.wrap($body).find('#buttonSubmitBillingAddressAdditional').click({ force: true});

        //Dynamically find the the paymentmethod based on the input and presses the relavant button. 
        cy.wait(buttonPressWaitTime);
        cy.wrap($body).find('[data-paymentmethod="' + input +'"]').eq(1).click({ force: true});

        //Presses the button to finnish the Iframe purchase. 
        cy.wait(buttonPressWaitTime);
        cy.wrap($body).find('.button-step2-enabled').click({ force: true});
       
        });
        /*Cypress cannot finnish loading the webpage, however despite this is the order finnished properly. To solve this do we reload the page manually which in turn directs
        us to the successURL from where we can get some of the information we need to check.*/
        cy.wait(checkoutLoadWaitTime);
        cy.reload();
        cy.wait(checkoutLoadWaitTime);
})
Cypress.Commands.add('comparePrices', (input) => {
    //Initizes the string ordernumber, and orderCostAtPurchase. 
    let orderID = '';
    let orderCostAtPurchase = '';
    cy.get('.woocommerce-Price-amount.amount').eq(0).invoke('text').then(orderCostAtPurchaseinput=> {
        cy.get('.woocommerce-Price-currencySymbol').eq(0).invoke('text').then(orderCurrencyinput=> {

            /*Get the information, in this case the price we paid and what currency we paid in from the success URL window. We then remove the currency to ensure we just keep 
            the number as not every place where it is saved saves the currency and sometimes just include the number itself.*/
            orderCostAtPurchase = orderCostAtPurchaseinput.replace(orderCurrencyinput,'');
            cy.get('.woocommerce-order-overview__order.order').eq(0).invoke('text').then(orderIDinput=> {

                /*the order number is saved from the sucess URL page to give us the ability to search for it, however since it is part of a string with other words on the 
                success URL must we also clean it by removing the letters*/
                orderID = orderIDinput.replace(/\D/g,'');
                cy.log("Order number is: " + orderID);
                cy.log("Order cost is: " + orderCostAtPurchase);
                cy.wait(purchaseWaitTime);

                //We login on wordpress admin to check where the order is saved. 
                cy.loginWpAdmin();
                cy.get('#toplevel_page_woocommerce').click();
                cy.wait(buttonPressWaitTime);

                //the order that corresponds to the ordernumber is accessed. 
                cy.get('#post-' + orderID).click();

                //We get the price that the order is saved as and compare it to what was written on the success URL. 
                cy.get('.wc-order-data-row.wc-order-totals-items.wc-order-items-editable').find('.woocommerce-Price-amount.amount').invoke('text').then(orderCostInStore=> {
                    expect(orderCostInStore).to.include(orderCostAtPurchase);
                });

                /*Finds the billmate invoice ID */
                cy.get('#the-list').find('tr').eq(0).find('td').eq(1).find('textarea').invoke('text').then(billmateInvoiceID=> {
                    cy.log(billmateInvoiceID);
                    //Makes a billmate API getpayment call to get the information saved on our website.
                    cy.task('paymentInfo', billmateInvoiceID).then((response) => {
                        let apiCallbackTotal = response.data.Cart.Total.withtax;
                        //divides the withtax value by 100 to convert the price from öre to SEK.
                        apiCallbackTotal = apiCallbackTotal/100;
                        cy.log("Api callback total is: " + apiCallbackTotal);
                        //compares the value that was noted when we finished the purchase to the value we got from the API call. 
                        expect(orderCostAtPurchase).to.include(apiCallbackTotal);
                        //Makes sure that the payment method in the API information is the same as the one we purchased. 
                        expect(response.data.PaymentData.method).to.include(input);
                    });
                });
            });
        });
    });
})
Cypress.Commands.add('duplicateItemsCheck', () => {
    /*Finds the billmate invoice ID in the order view*/
    cy.get('#the-list').find('tr').eq(0).find('td').eq(1).find('textarea').invoke('text').then(billmateInvoiceID=> {
        cy.log("The BillmateinvoiceID is: " + billmateInvoiceID);
        //Makes a billmate API getpayment call to get the information saved on our website.
        cy.task('paymentInfo', billmateInvoiceID).then((response) => {
            let getPaymentInfoArticles = response.data.Articles;
            /*Makes a for loop that cyckles through all items and then cyckles through all items ahead of item _i. Then compares item _i with item _p to ensure that they are 
            not the same to ensure we have no duplicates. The last article is not checked because it has already been compared to every article*/
            for(var _i = 0; _i < getPaymentInfoArticles.length - 1; _i++) {
                for(var _p = _i + 1; _p < getPaymentInfoArticles.length; _p++) {
                    //Items are converted to strings to make the test easier to read. 
                    expect(Object.values(getPaymentInfoArticles[_i]).toString()).to.not.equal(Object.values(getPaymentInfoArticles[_p]).toString());
                }
            }
        });
    });
})

export { }