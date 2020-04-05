const getCheckoutIframe = () => {
    return cy.get('#checkout')
                .its('0.contentDocument')
                .should('exist')
                .its('body')
                .then(cy.wrap);
}


Cypress.Commands.add('checkoutFillStepOne', (email, pno, zip) => {
    getCheckoutIframe().find('#email').type(email);
    getCheckoutIframe().find('#pno').type(pno);
    getCheckoutIframe().find('#button-step1').click();
});


Cypress.Commands.add('checkoutCompleteStepTwoWithMethod', (paymentMethod) => {
    getCheckoutIframe().find(`[data-method="${paymentMethod}"]`).click();
    getCheckoutIframe().find('#button-step2').click();
});

