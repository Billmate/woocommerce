

Cypress.Commands.add('products', () => {
    cy.get('#site-header').contains('Products').click();
})

Cypress.Commands.add('cart', () => {
    cy.get('#site-header').contains('Cart').click();
})

Cypress.Commands.add('checkout', () => {
    cy.get('#site-header').contains('Checkout').click();
})

Cypress.Commands.add('billmateCheckout', () => {
    cy.get('#site-header').contains('BillmateCheckout').click();
})