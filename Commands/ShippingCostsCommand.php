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
use OstArticleShippingCosts\Services\ConfigurationServiceInterface;
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
     * @var ConfigurationServiceInterface
     */
    private $configurationService;



    public function __construct(ArticleShippingCostCalculator $articleShippingCostCalculator, ConfigurationServiceInterface $configurationService)
    {
        parent::__construct('sc:set');
        $this->articleShippingCostCalculator = $articleShippingCostCalculator;
        $this->configurationService = $configurationService;
    }



    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this->setDescription('Sets the Shipping Costs in Attribute21')
            ->setHelp('The <info>%command.name%</info> sets the Shipping Costs in Attribute21 for all Articles.');
    }



    /**
     * {@inheritdoc}
     *
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        /** @var Detail[] $articleDetails */
        $articleDetails = Shopware()->Models()->getRepository(Detail::class)->findAll();

        $total = count($articleDetails);

        $progressBar = new ProgressBar($output, $total);
        $progressBar->start();

        foreach ($articleDetails as $articleDetail) {
            $attributes = $articleDetail->getAttribute();

            $attributes->fromArray([
                $this->configurationService->get('attributeTag') => $this->articleShippingCostCalculator->getShippingCosts($articleDetail)
            ]);

            Shopware()->Models()->persist($attributes);

            $progressBar->advance();
        }

        Shopware()->Models()->flush();

        $progressBar->finish();
        $output->writeln('');
    }
}
