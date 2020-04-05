/// <reference path="../support/commands.d.ts" />

describe('Test cases for Billmate checkout integration', () => {
    it('Customer can pay with invoice', () => {
        cy.visitStore();
        cy.addProductByName('Album');
        cy.addProductByName('Hoodie with Zipper');
        cy.billmateCheckout();
        cy.checkoutFillStepOne('test@example.com', '195501011018', 'test');
        cy.wait(5000);
        cy.checkoutCompleteStepTwoWithMethod(1);
    });
});