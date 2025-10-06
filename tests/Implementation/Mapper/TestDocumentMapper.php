<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Mapper;

use Honey\ODM\Core\Mapper\DocumentMapperInterface;
use Honey\ODM\Core\Mapper\DocumentMapperTrait;

final readonly class TestDocumentMapper implements DocumentMapperInterface
{
    use DocumentMapperTrait;
}
