<?php

declare(strict_types=1);

namespace Keven\Fortuneo;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class Client
{
    private HttpClientInterface $httpClient;
    private string $identifier, $password;
    private bool $isAuthenticated = false;
    private ?string $defaultBankaccount = null;

    public function __construct(string $identifier, string $password, string $defaultBankAccount = null, HttpClientInterface $httpClient = null)
    {
        $this->identifier = $identifier;
        $this->password = $password;
        $this->defaultBankaccount = $defaultBankAccount;
        $this->httpClient = $httpClient ?: HttpClient::createForBaseUri('https://mabanque.fortuneo.fr');
    }

    private function authenticateIfRequired(): void
    {
        if (!$this->isAuthenticated) {
            $this->authenticate($this->identifier, $this->password);
            $this->isAuthenticated = true;
        }
    }

    private function authenticate(string $identifier, string $password): void
    {
        $response = $this->httpClient->request('POST', '/checkacces', [
            'body' => [
                'login' => $identifier,
                'passwd' => $password,
            ]
        ]);

        if (!preg_match('/\/logoff/', $response->getContent())) {
            throw new \RuntimeException('Authentication failed.');
        }
    }

    public function findOperations(\DatePeriod $period, string $bankAccount = null): array
    {
        $this->authenticateIfRequired();

        return $this->extractOperationsFromCsv(
            $this->downloadCSV($period, $bankAccount)
        );
    }

    private function downloadCSV(\DatePeriod $period, string $bankAccount = null): string
    {
        $data = [
            'formatSelectionner' => 'csv',
            'dateRechercheDebut' => $period->getStartDate()->format('d/m/Y'),
            'dateRechercheFin'   => $period->getEndDate()->format('d/m/Y'),
            'triEnDate'          => 0,
        ];

        // Trigger the creation of a ZIP archive
        $response = $this->httpClient->request(
            'POST',
            '/fr/prive/mes-comptes/compte-courant/consulter-situation/telecharger-historique/telechargement-especes.jsp',
            ['body' => $data,],
        );

        file_put_contents('/tmp/page.html', $response->getContent());
var_dump($response->getStatusCode());
var_dump($response->getInfo());
        if (false === strpos($response->getContent(), 'Lancer le téléchargement')) {
            throw new \RuntimeException('Archive creation failed.');
        }

        // Download the archive
        $response = $this->httpClient->request(
            'GET',
            '/documents/HistoriqueOperations_'.$this->getBankAccount($bankAccount).'.zip',
        );
        $tmpfname = tempnam(sys_get_temp_dir(), 'fortuneo');
        $fileHandler = fopen($tmpfname, 'w+');
        foreach ($this->httpClient->stream($response) as $chunk) {
            fwrite($fileHandler, $chunk->getContent());
        }

        $zip = new \ZipArchive();
        $zip->open($tmpfname);
        for ($n = 0 ; $n < count($zip) ; $n++) {
            $filename = $zip->getFromIndex($n);

            if (!$this->strEndsWith($filename, '.csv')) {
                continue;
            }

            $csvFilename = tempnam(sys_get_temp_dir(), 'fortuneo').'.csv';
            $fp = $zip->getStream($filename);
            $ofp = fopen($csvFilename, 'w+');
            if (!$fp || !$ofp) {
                throw new \RuntimeException('Unable to extract the CSV file.');
            }

            while (!feof($fp)) {
                fwrite( $ofp, fread($fp, 8192) );
            }

            fclose($fp);
            fclose($ofp);

            // Only 1 CSV file is processed...
            break;
        }

        return $csvFilename;
    }

    private function extractOperationsFromCsv(string $filename): array
    {
        return fgetcsv(fopen($filename, 'r'));
    }

    private function getBankAccount(string $bankAccount = null): string
    {
        return
            $bankAccount ?:
            $this->findDefaultBankAccount()
        ;
    }

    private function findDefaultBankAccount(): string
    {
        if (null !== $this->defaultBankaccount) {
            return $this->defaultBankaccount;
        }

        throw new \LogicException(__METHOD__.' not implemented');
    }

    private function strEndsWith(string $haystack, string $needle): bool
    {
        $needle_len = strlen($needle);

        return ($needle_len === 0 || 0 === substr_compare($haystack, $needle, - $needle_len));
    }
}
