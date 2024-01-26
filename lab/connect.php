<?php

use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\WebDriverBy;
use Keven\Fortuneo\Client as Fortuneo;
use Keven\Fortuneo\PeriodBuilder;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Panther\Client;

require_once dirname(__DIR__).'/vendor/autoload.php';

$fortuneo = new Fortuneo('x', 'x');
var_dump($fortuneo->findOperations(PeriodBuilder::from('2 years ago')->untilNow()));
die;

$browser = new HttpBrowser($client = HttpClient::createForBaseUri('https://mabanque.fortuneo.fr'));
//$crawler = $browser->request('GET', '/fr/identification.jsp');
//$response = $browser->submitForm('Valider', [
//    'login' => 'x',
//    'passwd' => 'x',
//    'idDyn' => false,
//    'locale' => 'fr',
//]);
//
//var_dump($response);
//var_dump($browser->getResponse());

$response = $client->request('POST', '/checkacces', [
    'body' => [
        'login' => 'x',
        'passwd' => 'x',
    ]
]);

var_dump(preg_match('/\/logoff/', $response->getContent()));

$urlDl = 'https://mabanque.fortuneo.fr/fr/prive/mes-comptes/compte-courant/consulter-situation/telecharger-historique/telechargement-especes.jsp';
$urlCsv = $urlDl.'https://mabanque.fortuneo.fr/documents/HistoriqueOperations_';

die;

$client = Client::createChromeClient(dirname(__DIR__).'/drivers/chromedriver');
$client->request('GET', 'https://mabanque.fortuneo.fr/fr/identification.jsp');
//$crawler = $client->submitForm('valider_bv', [
//    'login' => 'x',
//    'passwd' => 'x',
//    'idDyn' => false,
//    'locale' => 'fr',
//]);
$client->findElement(WebDriverBy::name('LOGIN'))->sendKeys('x');
$client->findElement(WebDriverBy::name('PASSWD'))->sendKeys('x');

$start = time();
$timeoutInSec = 30;
while (time() - $start < $timeoutInSec) {
    try {
        $client->findElement(WebDriverBy::id('valider_login'))->click();
        break;
    } catch (ElementClickInterceptedException $e) {
        usleep(100_000); // 100 ms
        continue;
    }
}
sleep(2);
var_dump($client->getHistory());
$client->waitFor('#colonnecompte_client');
var_dump($client->getPageSource());
