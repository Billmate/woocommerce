/// <reference path="../support/commands.d.ts" />

describe('Wordpress', () => {

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

});