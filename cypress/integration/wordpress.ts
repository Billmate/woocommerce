describe('Wordpress', () => {

    it('Check that Wordpress site is up and running', () => {
        expect(cy.visit(Cypress.env('host'))).to.be.ok;
    });

    /*it('Login', function() {
        cy.visit('https://online.billmate.se');
        cy.get('#email').should('be.visible').type('oskar.ostlund');
        cy.get('#password').should('be.visible').type('Mark1Mark2');
        cy.get('#loginForm .buttonshort').click();
    });*/

});