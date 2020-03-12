import { Selector, ClientFunction } from 'testcafe';

fixture `Wordpress`
    .page `http://localhost:8282`;

// Returns the URL of the current web page
const getPageUrl = ClientFunction(() => window.location.href);

test('Check that Wordpress site is up and running', async t => {
    await t.expect(getPageUrl()).contains('http://localhost:8282');
});