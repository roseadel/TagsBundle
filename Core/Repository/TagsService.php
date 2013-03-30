<?php

namespace EzSystems\TagsBundle\Core\Repository;

use eZ\Publish\API\Repository\Repository;
use EzSystems\TagsBundle\API\Repository\TagsService as TagsServiceInterface;
use EzSystems\TagsBundle\SPI\Persistence\Tags\Handler;
use EzSystems\TagsBundle\API\Repository\Values\Tags\Tag;
use EzSystems\TagsBundle\API\Repository\Values\Tags\TagList;
use EzSystems\TagsBundle\API\Repository\Values\Tags\TagCreateStruct;
use EzSystems\TagsBundle\API\Repository\Values\Tags\TagUpdateStruct;
use EzSystems\TagsBundle\SPI\Persistence\Tags\Tag as SPITag;
use EzSystems\TagsBundle\SPI\Persistence\Tags\CreateStruct;
use EzSystems\TagsBundle\SPI\Persistence\Tags\UpdateStruct;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentException;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentValue;
use DateTime;
use Exception;

class TagsService implements TagsServiceInterface
{
    /**
     * @var \eZ\Publish\API\Repository\Repository
     */
    protected $repository;

    /**
     * @var \EzSystems\TagsBundle\SPI\Persistence\Tags\Handler
     */
    protected $tagsHandler;

    /**
     * Constructor
     *
     * @param \eZ\Publish\API\Repository\Repository $repository
     * @param \EzSystems\TagsBundle\SPI\Persistence\Tags\Handler $tagsHandler
     */
    public function __construct( Repository $repository, Handler $tagsHandler )
    {
        $this->repository = $repository;
        $this->tagsHandler = $tagsHandler;
    }

    /**
     * Loads a tag object from its $tagId
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException If the current user is not allowed to read this tag
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException If the specified tag is not found
     *
     * @param mixed $tagId
     *
     * @return \EzSystems\TagsBundle\API\Repository\Values\Tags\Tag
     */
    public function loadTag( $tagId )
    {
        $spiTag = $this->tagsHandler->load( $tagId );
        return $this->buildTagDomainObject( $spiTag );
    }

    /**
     * Loads a tag object from its $remoteId
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException If the current user is not allowed to read this tag
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException If the specified tag is not found
     *
     * @param string $remoteId
     *
     * @return \EzSystems\TagsBundle\API\Repository\Values\Tags\Tag
     */
    public function loadTagByRemoteId( $remoteId )
    {
        $spiTag = $this->tagsHandler->loadByRemoteId( $remoteId );
        return $this->buildTagDomainObject( $spiTag );
    }

    /**
     * Loads children of a tag object
     *
     * @param \EzSystems\TagsBundle\API\Repository\Values\Tags\Tag $tag
     * @param int $offset The start offset for paging
     * @param int $limit The number of tags returned. If $limit = -1 all children starting at $offset are returned
     *
     * @return \EzSystems\TagsBundle\API\Repository\Values\Tags\TagList
     */
    public function loadTagChildren( Tag $tag, $offset = 0, $limit = -1 )
    {
    }

    /**
     * Returns the number of children of a tag object
     *
     * @param \EzSystems\TagsBundle\API\Repository\Values\Tags\Tag $tag
     *
     * @return int
     */
    public function getTagChildCount( Tag $tag )
    {
    }

    /**
     * Creates the new tag
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException If the current user is not allowed to create this tag
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException If the remote ID already exists
     *
     * @param \EzSystems\TagsBundle\API\Repository\Values\Tags\TagCreateStruct $tagCreateStruct
     *
     * @return \EzSystems\TagsBundle\API\Repository\Values\Tags\Tag The newly created tag
     */
    public function createTag( TagCreateStruct $tagCreateStruct )
    {
        if ( !is_numeric( $tagCreateStruct->parentTagId ) )
        {
            throw new InvalidArgumentValue( "parentTagId", $tagCreateStruct->parentTagId, "TagCreateStruct" );
        }

        if ( empty( $tagCreateStruct->keyword ) || !is_string( $tagCreateStruct->keyword ) )
        {
            throw new InvalidArgumentValue( "keyword", $tagCreateStruct->keyword, "TagCreateStruct" );
        }

        if ( $tagCreateStruct->remoteId !== null && ( empty( $tagCreateStruct->remoteId ) || !is_string( $tagCreateStruct->remoteId ) ) )
        {
            throw new InvalidArgumentValue( "remoteId", $tagCreateStruct->remoteId, "TagCreateStruct" );
        }

        // check for existence of tag with provided remote ID
        if ( $tagCreateStruct->remoteId !== null )
        {
            try
            {
                $this->tagsHandler->loadByRemoteId( $tagCreateStruct->remoteId );
                throw new InvalidArgumentException( "tagCreateStruct", "Tag with provided remote ID already exists" );
            }
            catch ( NotFoundException $e )
            {
                // Do nothing
            }
        }
        else
        {
            $tagCreateStruct->remoteId = md5( uniqid( get_class( $this ), true ) );
        }

        $createStruct = new CreateStruct();
        $createStruct->parentTagId = $tagCreateStruct->parentTagId;
        $createStruct->keyword = $tagCreateStruct->keyword;
        $createStruct->remoteId = $tagCreateStruct->remoteId;

        $this->repository->beginTransaction();
        try
        {
            $newTag = $this->tagsHandler->create( $createStruct );
            $this->repository->commit();
        }
        catch ( Exception $e )
        {
            $this->repository->rollback();
            throw $e;
        }

        return $this->buildTagDomainObject( $newTag );
    }

    /**
     * Updates $tag
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException If the specified tag is not found
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException If the current user is not allowed to update this tag
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException If the remote ID already exists
     *
     * @param \EzSystems\TagsBundle\API\Repository\Values\Tags\Tag $tag
     * @param \EzSystems\TagsBundle\API\Repository\Values\Tags\TagUpdateStruct $tagUpdateStruct
     *
     * @return \EzSystems\TagsBundle\API\Repository\Values\Tags\Tag The updated tag
     */
    public function updateTag( Tag $tag, TagUpdateStruct $tagUpdateStruct )
    {
        if ( !is_numeric( $tag->id ) )
        {
            throw new InvalidArgumentValue( "id", $tag->id, "Tag" );
        }

        if ( $tagUpdateStruct->keyword !== null && ( !is_string( $tagUpdateStruct->keyword ) || empty( $tagUpdateStruct->keyword ) ) )
        {
            throw new InvalidArgumentValue( "keyword", $tagUpdateStruct->keyword, "TagUpdateStruct" );
        }

        if ( $tagUpdateStruct->remoteId !== null && ( !is_string( $tagUpdateStruct->remoteId ) || empty( $tagUpdateStruct->remoteId ) ) )
        {
            throw new InvalidArgumentValue( "remoteId", $tagUpdateStruct->remoteId, "TagUpdateStruct" );
        }

        $spiTag = $this->tagsHandler->load( $tag->id );

        if ( $tagUpdateStruct->remoteId !== null )
        {
            try
            {
                $existingTag = $this->tagsHandler->loadByRemoteId( $tagUpdateStruct->remoteId );
                if ( $existingTag->remoteId !== $spiTag->remoteId )
                {
                    throw new InvalidArgumentException( "tagUpdateStruct", "Tag with provided remote ID already exists" );
                }
            }
            catch ( NotFoundException $e )
            {
                // Do nothing
            }
        }

        $updateStruct = new UpdateStruct();
        $updateStruct->keyword = $tagUpdateStruct->keyword !== null ? trim( $tagUpdateStruct->keyword ) : $spiTag->keyword;
        $updateStruct->remoteId = $tagUpdateStruct->remoteId !== null ? trim( $tagUpdateStruct->remoteId ) : $spiTag->remoteId;

        $this->repository->beginTransaction();
        try
        {
            $updatedTag = $this->tagsHandler->update( $updateStruct, $spiTag->id );
            $this->repository->commit();
        }
        catch ( Exception $e )
        {
            $this->repository->rollback();
            throw $e;
        }

        return $this->buildTagDomainObject( $updatedTag );
    }

    /**
     * Creates a synonym for $tag
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException If the current user is not allowed to create a synonym
     *
     * @param \EzSystems\TagsBundle\API\Repository\Values\Tags\Tag $tag
     * @param string $keyword
     *
     * @return \EzSystems\TagsBundle\API\Repository\Values\Tags\Tag The created synonym
     */
    public function addSynonym( Tag $tag, $keyword )
    {
    }

    /**
     * Converts $tag to a synonym of $mainTag
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException If the current user is not allowed to convert tag to synonym
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException Tf the tag is already a synonym
     *
     * @param \EzSystems\TagsBundle\API\Repository\Values\Tags\Tag $tag
     * @param \EzSystems\TagsBundle\API\Repository\Values\Tags\Tag $mainTag
     *
     * @return \EzSystems\TagsBundle\API\Repository\Values\Tags\Tag The converted synonym
     */
    public function convertToSynonym( Tag $tag, Tag $mainTag )
    {
    }

    /**
     * Merges the $tag into the $targetTag
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException If the current user is not allowed to merge tags
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException If either one of the tags is a synonym
     *
     * @param \EzSystems\TagsBundle\API\Repository\Values\Tags\Tag $tag
     * @param \EzSystems\TagsBundle\API\Repository\Values\Tags\Tag $targetTag
     */
    public function mergeTags( Tag $tag, Tag $targetTag )
    {
    }

    /**
     * Swaps the locations of $tag1 and $tag2
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException If the current user is not allowed to swap tags
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException If either one of the tags is a synonym
     *
     * @param \EzSystems\TagsBundle\API\Repository\Values\Tags\Tag $tag1
     * @param \EzSystems\TagsBundle\API\Repository\Values\Tags\Tag $tag2
     */
    public function swapTag( Tag $tag1, Tag $tag2 )
    {
    }

    /**
     * Copies the subtree starting from $subtree as a new subtree of $targetParentTag
     *
     * Only the items on which the user has read access are copied
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException If the current user is not allowed copy the subtree to the given parent tag
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException If the current user does not have read access to the whole source subtree
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException If the target tag is a sub tag of the given tag
     *                                                                        If either one of the tags is a synonym
     *
     * @param \EzSystems\TagsBundle\API\Repository\Values\Tags\Tag $subtree The subtree denoted by the tag to copy
     * @param \EzSystems\TagsBundle\API\Repository\Values\Tags\Tag $targetParentTag The target parent tag for the copy operation
     *
     * @return \EzSystems\TagsBundle\API\Repository\Values\Tags\Tag The newly created tag of the copied subtree
     */
    public function copySubtree( Tag $subtree, Tag $targetParentTag )
    {
    }

    /**
     * Moves the subtree to $newParentTag
     *
     * If a user has the permission to move the tag to a target tag
     * he can do it regardless of an existing descendant on which the user has no permission
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException If the current user is not allowed to move this tag to the target
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException If the current user does not have read access to the whole source subtree
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException If either one of the tags is a synonym
     *
     * @param \EzSystems\TagsBundle\API\Repository\Values\Tags\Tag $tag
     * @param \EzSystems\TagsBundle\API\Repository\Values\Tags\Tag $newParentTag
     */
    public function moveSubtree( Tag $tag, Tag $newParentTag )
    {
    }

    /**
     * Deletes $tag and all its descendants and synonyms
     *
     * If $tag is a synonym, only the synonym is deleted
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException If the current user is not allowed to delete this tag or a descendant
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException If the specified tag is not found
     *
     * @param \EzSystems\TagsBundle\API\Repository\Values\Tags\Tag $tag
     */
    public function deleteTag( Tag $tag )
    {
        $this->repository->beginTransaction();
        try
        {
            $this->tagsHandler->deleteTag( $tag->id );
            $this->repository->commit();
        }
        catch ( Exception $e )
        {
            $this->repository->rollback();
            throw $e;
        }
    }

    /**
     * Instantiates a new tag create struct
     *
     * @param mixed $parentTagId
     * @param string $keyword
     *
     * @return \EzSystems\TagsBundle\API\Repository\Values\Tags\TagCreateStruct
     */
    public function newTagCreateStruct( $parentTagId, $keyword )
    {
        $tagCreateStruct = new TagCreateStruct();
        $tagCreateStruct->parentTagId = $parentTagId;
        $tagCreateStruct->keyword = $keyword;

        return $tagCreateStruct;
    }

    /**
     * Instantiates a new tag update struct
     *
     * @return \EzSystems\TagsBundle\API\Repository\Values\Tags\TagUpdateStruct
     */
    public function newTagUpdateStruct()
    {
        return new TagUpdateStruct();
    }

    protected function buildTagDomainObject( SPITag $spiTag )
    {
        $modificationDate = new DateTime();
        $modificationDate->setTimestamp( $spiTag->modificationDate );

        return new Tag(
            array(
                 "id" => $spiTag->id,
                 "parentTagId" => $spiTag->parentTagId,
                 "mainTagId" => $spiTag->mainTagId,
                 "keyword" => $spiTag->keyword,
                 "depth" => $spiTag->depth,
                 "pathString" => $spiTag->pathString,
                 "modificationDate" => $modificationDate,
                 "remoteId" => $spiTag->remoteId
            )
        );
    }
}