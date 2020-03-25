describe('Wordpress', () => {

    it('Check that Wordpress site is up and running', () => {
        expect(cy.visit(Cypress.env('host'))).to.be.ok;
    });

});
