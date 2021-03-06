<?php

/**
 * Copyright 2016 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace CloudCreativity\JsonApi\Http;

use CloudCreativity\JsonApi\Contracts\Http\Requests\RequestInterpreterInterface;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use CloudCreativity\JsonApi\Repositories\CodecMatcherRepository;
use CloudCreativity\JsonApi\Repositories\SchemasRepository;
use CloudCreativity\JsonApi\Store\Store;
use CloudCreativity\JsonApi\TestCase;
use Neomerx\JsonApi\Contracts\Http\HttpFactoryInterface;
use Neomerx\JsonApi\Factories\Factory;

/**
 * Class ApiFactoryTest
 * @package CloudCreativity\JsonApi
 */
class ApiFactoryTest extends TestCase
{

    /**
     * @var CodecMatcherRepository
     */
    private $codecMatchers;

    /**
     * @var SchemasRepository
     */
    private $schemas;

    /**
     * @var Store
     */
    private $store;

    /**
     * @var RequestInterpreterInterface
     */
    private $requestInterpreter;

    /**
     * @var HttpFactoryInterface
     */
    private $httpFactory;

    /**
     * @var ApiFactory
     */
    private $factory;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->codecMatchers = new CodecMatcherRepository();
        $this->schemas = new SchemasRepository();
        $this->store = new Store();
        $this->requestInterpreter = $this->getMockBuilder(RequestInterpreterInterface::class)->getMock();
        $this->httpFactory = new Factory();

        $this->factory = new ApiFactory(
            $this->codecMatchers,
            $this->schemas,
            $this->store,
            $this->requestInterpreter,
            $this->httpFactory
        );

        $this->schemas->configure([
            'defaults' => [
                'Person' => 'Api\People\Schema',
                'Post' => 'Api\Posts\Schema',
                'Comment' => 'Api\Comments\Schema'
            ],
            'v1' => [
            ],
            'v2' => [
                'Person' => 'Api\People\V2\Schema'
            ],
        ]);
    }

    public function testApiV1()
    {
        $this->factory->configure(['v1' => []]);
        $api = $this->factory->createApi('v1');
        $this->assertSame('v1', $api->getNamespace());
        $this->assertSame($this->store, $api->getStore());
        $this->assertSame($this->requestInterpreter, $api->getRequestInterpreter());
        $this->assertSame($this->httpFactory, $api->getHttpFactory());
    }

    public function testDefaultPagingStrategy()
    {
        $this->factory->configure(['v1' => []]);
        $api = $this->factory->createApi('v1');

        $this->assertSame('number', $api->getPagingStrategy()->getPage());
        $this->assertSame('size', $api->getPagingStrategy()->getPerPage());
    }

    public function testPagingStrategy()
    {
        $this->factory->configure([
            'v1' => [
                'paging' => [
                    'page' => 'foo',
                    'per-page' => 'bar',
                ],
            ],
        ]);

        $api = $this->factory->createApi('v1');
        $this->assertEquals('foo', $api->getPagingStrategy()->getPage());
        $this->assertEquals('bar', $api->getPagingStrategy()->getPerPage());
    }

    public function testOtherOptions()
    {
        $expected = ['paging-meta' => ['key' => 'page']];
        $config = array_merge([
            'url-prefix' => '/api/v1',
            'supported-ext' => null,
            'paging' => ['page' => 'foo'],
        ], $expected);

        $this->factory->configure([
            'v1' => $config,
        ]);

        $api = $this->factory->createApi('v1');
        $this->assertEquals($expected, $api->getOptions());
    }

    public function testInvalidApiNamespace()
    {
        $this->setExpectedException(RuntimeException::class, 'v2');

        $this->factory->configure(['v1' => []]);
        $this->factory->createApi('v2');
    }
}
