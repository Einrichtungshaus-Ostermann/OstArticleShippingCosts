<?php declare(strict_types=1);

/**
 * Einrichtungshaus Ostermann GmbH & Co. KG - Article Shipping Costs
 *
 * Article Shipping Costs calculator
 *
 * @package   OstArticleShippingCosts
 *
 * @author    Tim Windelschmidt <tim.windelschmidt@ostermann.de>
 * @copyright 2018 Einrichtungshaus Ostermann GmbH & Co. KG
 * @license   proprietary
 */

namespace OstArticleShippingCosts\Commands;

use OstArticleShippingCosts\Services\ArticleShippingCostCalculator;
use Shopware\Commands\ShopwareCommand;
use Shopware\Models\Article\Detail;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShippingCostsCommand extends ShopwareCommand
{
    /**
     * @var ArticleShippingCostCalculator
     */
    private $articleShippingCostCalculator;

    /**
     * @var array
     */
    private $configuration;

    /**
     * @param ArticleShippingCostCalculator $articleShippingCostCalculator
     * @param array                         $configuration
     */
    public function __construct(ArticleShippingCostCalculator $articleShippingCostCalculator, array $configuration)
    {
        parent::__construct('sc:set');
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
        $this->setDescription('Sets the Shipping Costs in Attribute')
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
        $query = '
            SELECT id
            FROM s_articles_details
            ORDER BY ordernumber ASC
        ';
        $ids = Shopware()->Db()->fetchAll($query);

        $total = count($ids);

        $progressBar = new ProgressBar($output, $total);
        $progressBar->start();

        foreach ($ids as $arr) {
            $id = $arr['id'];

            /** @var Detail $articleDetail */
            $articleDetail = Shopware()->Models()->find(Detail::class, $id);

            $attributes = $articleDetail->getAttribute();

            $attributes->fromArray([
                $this->configuration['attributeShippingCosts'] => $this->articleShippingCostCalculator->getShippingCosts($articleDetail)
            ]
            );

            Shopware()->Models()->flush($attributes);

            $progressBar->advance();
        }

        $progressBar->finish();
        $output->writeln('');
    }
}
