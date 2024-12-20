<?php
/**
 * Copyright © Qoliber. All rights reserved.
 *
 * @category    Qoliber
 * @package     Qoliber_ProductPack
 * @author      Jakub Winkler <jwinkler@qoliber.com>
 * @author      Wojciech M. Wnuk <wwnuk@qoliber.com>
 * @author      Łukasz Owczarczuk <lowczarczuk@qoliber.com>
 */

declare(strict_types=1);

namespace Qoliber\ProductPack\Model;

use Exception;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Qoliber\ProductPack\Api\Data\PackOptionInterface;
use Qoliber\ProductPack\Api\Data\PackOptionInterfaceFactory;
use Qoliber\ProductPack\Api\Data\PackOptionSearchResultsInterfaceFactory;
use Qoliber\ProductPack\Api\PackOptionRepositoryInterface;
use Qoliber\ProductPack\Model\ResourceModel\PackOption as ResourcePackOption;
use Qoliber\ProductPack\Model\ResourceModel\PackOption\CollectionFactory as PackOptionCollectionFactory;

class PackOptionRepository implements PackOptionRepositoryInterface
{
    protected ResourcePackOption $resource;

    protected PackOptionFactory $packOptionFactory;

    protected PackOptionCollectionFactory $packOptionCollectionFactory;

    protected PackOptionSearchResultsInterfaceFactory $searchResultsFactory;

    protected DataObjectHelper $dataObjectHelper;

    protected DataObjectProcessor $dataObjectProcessor;

    protected PackOptionInterfaceFactory $dataPackOptionFactory;

    protected JoinProcessorInterface $extensionAttributesJoinProcessor;

    private readonly CollectionProcessorInterface $collectionProcessor;

    protected ExtensibleDataObjectConverter $extensibleDataObjectConverter;

    /**
     * @param PackOptionFactory $packOptionFactory
     * @param PackOptionInterfaceFactory $dataPackOptionFactory
     * @param PackOptionSearchResultsInterfaceFactory $searchResultsFactory
     */
    public function __construct(
        ResourcePackOption $resource,
        PackOptionFactory $packOptionFactory,
        PackOptionInterfaceFactory $dataPackOptionFactory,
        PackOptionCollectionFactory $packOptionCollectionFactory,
        PackOptionSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->packOptionFactory = $packOptionFactory;
        $this->packOptionCollectionFactory = $packOptionCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataPackOptionFactory = $dataPackOptionFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->collectionProcessor = $collectionProcessor;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        PackOptionInterface $packOption
    ) {
        $packOptionData = $this->extensibleDataObjectConverter->toNestedArray(
            $packOption,
            [],
            PackOptionInterface::class
        );

        $packOptionModel = $this->packOptionFactory->create()->setData($packOptionData);

        try {
            $this->resource->save($packOptionModel);
        } catch (Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the packOption: %1',
                $exception->getMessage()
            ));
        }

        return $packOptionModel;
    }

    /**
     * {@inheritdoc}
     */
    public function get($packOptionId)
    {
        $packOption = $this->packOptionFactory->create();
        $this->resource->load($packOption, $packOptionId);
        if (!$packOption->getId()) {
            throw new NoSuchEntityException(__('PackOption with id "%1" does not exist.', $packOptionId));
        }

        return $packOption;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        SearchCriteriaInterface $searchCriteria
    ) {
        $collection = $this->packOptionCollectionFactory->create();

        $this->extensionAttributesJoinProcessor->process(
            $collection,
            PackOptionInterface::class
        );

        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);

        $items = [];
        foreach ($collection as $model) {
            $items[] = $model;
        }

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        PackOptionInterface $packOption
    ) {
        try {
            $packOptionModel = $this->packOptionFactory->create();
            $this->resource->load($packOptionModel, $packOption->getPackoptionId());
            $this->resource->delete($packOptionModel);
        } catch (Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the PackOption: %1',
                $exception->getMessage()
            ));
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($packOptionId)
    {
        return $this->delete($this->get($packOptionId));
    }
}
