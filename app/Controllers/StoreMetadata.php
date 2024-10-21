<?php

namespace App;

use DOMDocument;
use DOMXPath;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StoreMetadata extends Command
{
    private readonly string $baseUrl;
    private readonly string $reviewPageUrl;
    public function __construct()
    {
        parent::__construct();

        $this->baseUrl       = 'https://www.cochranelibrary.com';
        $this->reviewPageUrl = 'search?resultPerPage=100&p_p_id=scolarissearchresultsportlet_WAR_scolarissearchresults&p_p_lifecycle=0&p_p_state=normal&p_p_mode=view&p_p_col_id=column-1&p_p_col_count=1&_scolarissearchresultsportlet_WAR_scolarissearchresults_displayText=Allergy+%26+intolerance&_scolarissearchresultsportlet_WAR_scolarissearchresults_searchText=Allergy+%26+intolerance&_scolarissearchresultsportlet_WAR_scolarissearchresults_searchType=basic&_scolarissearchresultsportlet_WAR_scolarissearchresults_facetQueryField=topic_id&_scolarissearchresultsportlet_WAR_scolarissearchresults_searchBy=13&_scolarissearchresultsportlet_WAR_scolarissearchresults_orderBy=displayDate-true&_scolarissearchresultsportlet_WAR_scolarissearchresults_facetDisplayName=Allergy+%26+intolerance&_scolarissearchresultsportlet_WAR_scolarissearchresults_facetQueryTerm=z1506030924307755598196034641807&_scolarissearchresultsportlet_WAR_scolarissearchresults_facetCategory=Topics';
    }
    public static function getDefaultName(): ?string
    {
        return "store-metadata";
    }

    /*protected function configure(): void
    {
        $this->setDefinition(
            new InputDefinition([
                new InputOption('foo', null, InputOption::VALUE_NONE, 'Foo option'),
                new InputOption('bar', null, InputOption::VALUE_NONE, 'Bar option'),
            ])
        );
    }*/

    /**
     * @throws GuzzleException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $start_time = microtime(true);
        $this->storeMetadata();
        $end_time = microtime(true);

        $execution_time = $end_time - $start_time;

        echo "Execution time: " . $execution_time . " seconds";

        return Command::SUCCESS;
    }

    /**
     * @throws GuzzleException
     */
    private function storeMetadata(): void
    {
        $client = new Client([
            'base_uri'  => $this->baseUrl,
            'headers'   => [
                'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4183.121 Safari/537.36',
            ],
        ]);

        $response = $client->get(uri: $this->reviewPageUrl);

        $this->scrappingWithDom(responseBody: $response->getBody()->getContents());
    }

    private function scrappingWithDom(string $responseBody): void
    {
        $dom   = new DOMDocument();
        @$dom->loadHTML(source: $responseBody);
        $xpath = new DOMXPath(document: $dom);

        $reviewNodes = $xpath->query(".//div[@class='search-results-item']");
        $this->storeReviewMetaData(reviewNodes: $reviewNodes, xpath: $xpath, dom: $dom);
    }

    private function storeReviewMetaData($reviewNodes, DOMXPath $xpath, DOMDocument $dom): void
    {
        $fileToWrite = fopen(filename: 'cochrane_reviews.txt', mode: 'w+');

        foreach ($reviewNodes as $node)
        {
            $dateNode    = $xpath->query(".//div[@class='search-result-date']", $node)->item(0);
            $titleNode   = $xpath->query(".//h3[@class='result-title']//a", $node)->item(0);
            $authorNode  = $xpath->query(".//div[@class='search-result-authors']//div", $node)->item(0);
            $reviewTopic = trim(string: $dom->getElementById('searchText')->getAttribute('value'));

            $url        = $titleNode->getAttribute(qualifiedName: 'href');
            $date       = $this->formatDate(date: $dateNode->nodeValue);
            $title      = trim(string: $titleNode->nodeValue);
            $author     = trim(string: $authorNode->nodeValue);

            $reviews = $this->baseUrl . $url . " | " . $reviewTopic . " | " . $title  . " | " . $author . " | " .  $date . "\n";
            fwrite($fileToWrite, $reviews);
        }

        fclose($fileToWrite);
    }

    private function formatDate(string $date): string
    {
        return date('Y-m-d', strtotime($date));
    }
}