///<reference path="../support/commands.d.ts" />
describe('Wordpress', function () {

    before(function () {
        cy.fixture('user.json').then((user) => {
            Cypress.env('user', user);  // Make value global
        });
        cy.clearLocalStorage();
                cy.window().then((win) => {
            win.sessionStorage.clear()
        });
    });
    
    beforeEach(function () {
        cy.wait(10);
        cy.clearCookies();
    });
    it('Check that Wordpress site is up and running', () => {
        expect(cy.visitStore()).to.be.ok;
    });

    it('Login to Wordpress admin', () => {
        expect(cy.visitWpAdmin());
        cy.get('#user_login').should('be.visible').type('admin');
        cy.get('#user_pass').should('be.visible').type('4dm1n');
        cy.get('#wp-submit').click();
        cy.get('#wp-admin-bar-my-account .display-name').contains('admin');
    });
    it('Make an invoice purchase company', () => {
        let purchaseWait = 2000;
        let buttonPressWait = 4000;
        let shortWait = 1000;
        cy.visitStore();
        cy.purchaseArticles();
        cy.wait(shortWait);
        /* what kind of invoice is made is determined what value the input is, 
        1: Invoice
        4: Part Payment.
        8: Card payment.
        16: bankpayment.
        1024: Swish.*/
        let purchaseMethod = '1';
        cy.fillOutIframeCompany(purchaseMethod);
        cy.comparePrices(purchaseMethod);
        cy.duplicateItemsCheck();
        
    });
    it('Make an invoice purchase person', () => {
        let purchaseWait = 2000;
        let buttonPressWait = 4000;
        let shortWait = 1000;
        cy.visitStore();
        cy.purchaseArticles();
        cy.wait(shortWait);
        /* what kind of invoice is made is determined what value the input is, 
        1: Invoice
        4: Part Payment.
        8: Card payment.
        16: bankpayment.
        1024: Swish.*/
        let purchaseMethod = '1';
        cy.fillOutIframePerson(purchaseMethod);
        cy.comparePrices(purchaseMethod);
        cy.duplicateItemsCheck();
        
    });
    it('Make a partpayment person', () => {
        let purchaseWait = 2000;
        let buttonPressWait = 4000;
        let shortWait = 1000;
        cy.visitStore();
        cy.purchaseArticles();
        cy.wait(shortWait);
        /* what kind of invoice is made is determined what value the input is, 
        1: Invoice
        4: Part Payment.
        8: Card payment.
        16: bankpayment.
        1024: Swish.*/
        let purchaseMethod = '4';
        cy.fillOutIframePerson(purchaseMethod);
        cy.comparePrices(purchaseMethod); 
        cy.duplicateItemsCheck();
    });
});
