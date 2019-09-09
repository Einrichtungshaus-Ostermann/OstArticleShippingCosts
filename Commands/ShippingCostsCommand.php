<?php declare(strict_types=1);

/**
 * Einrichtungshaus Ostermann GmbH & Co. KG - Article Shipping Costs
 *
 * @package   OstArticleShippingCosts
 *
 * @author    Tim Windelschmidt <tim.windelschmidt@ostermann.de>
 * @copyright 2018 Einrichtungshaus Ostermann GmbH & Co. KG
 * @license   proprietary
 */

namespace OstArticleShippingCosts\Commands;

use OstArticleShippingCosts\Services\ArticleShippingCostCalculatorInterface;
use Shopware\Commands\ShopwareCommand;
use Shopware\Models\Article\Detail;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShippingCostsCommand extends ShopwareCommand
{
    /**
     * @var ArticleShippingCostCalculatorInterface
     */
    private $articleShippingCostCalculator;

    /**
     * @var array
     */
    private $configuration;

    /**
     * @param ArticleShippingCostCalculatorInterface $articleShippingCostCalculator
     * @param array                                  $configuration
     */
    public function __construct(ArticleShippingCostCalculatorInterface $articleShippingCostCalculator, array $configuration)
    {
        parent::__construct('ost-article-shipping-costs:calculate');
        $this->articleShippingCostCalculator = $articleShippingCostCalculator;
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this->setDescription('Sets the shipping costs in attribute')
            ->setHelp('The <info>%command.name%</info> sets the Shipping Costs in Attribute for all Articles.');
    }

    /**
     * {@inheritdoc}
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // get every article
        $query = '
            SELECT id
            FROM s_articles_details
            ORDER BY ordernumber ASC
        ';
        $ids = Shopware()->Db()->fetchAll($query);

        // count it
        $total = count($ids);

        // set the progress bar
        $progressBar = new ProgressBar($output, $total);
        $progressBar->start();

        // loop every article
        foreach ($ids as $arr) {
            /** @var Detail $articleDetail */
            $articleDetail = Shopware()->Models()->find(Detail::class, $arr['id']);

            // get the attributes
            $attributes = $articleDetail->getAttribute();

            // get the shipping costs
            $costs = $this->articleShippingCostCalculator->getShippingCosts($articleDetail);

            // set them
            $attributes->fromArray([
                $this->configuration['attributeShippingCosts'] => $costs
            ]);

            // save the attributes
            Shopware()->Models()->flush($attributes);

            // advance the progress bar
            $progressBar->advance();
        }

        // and finish
        $progressBar->finish();
        $output->writeln('');
    }
}
