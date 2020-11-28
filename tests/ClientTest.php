<?php declare(strict_types=1);
namespace Sanity;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Sanity\Exception\ClientException;
use Sanity\Exception\ConfigException;
use Sanity\Exception\InvalidArgumentException;
use Sanity\Exception\ServerException;

class ClientTest extends TestCase
{
    private $client;
    private $history;

    /**
     * @doesNotPerformAssertions
     */
    public function testCanConstructNewClient()
    {
        new Client([
            'projectId' => 'abc',
            'dataset' => 'production',
            'apiVersion' => '2019-01-01',
        ]);
    }

    public function testWarnsOnNoApiVersionSpecified()
    {
        $this->expectDeprecation();
        $this->expectDeprecationMessage('Using the Sanity client without specifying an API version is deprecated');

        new Client([
            'projectId' => 'abc',
            'dataset' => 'production',
        ]);
    }

    public function testWarnsOnServerWarnings()
    {
        $this->client = new Client([
            'projectId' => 'abc',
            'dataset' => 'production',
            'apiVersion' => '1',
        ]);
        $this->mockResponses([$this->mockJsonResponseBody(['result' => []], 200, ['X-Sanity-Warning' => 'Some error'])]);

        $this->expectWarning();
        $this->expectWarningMessage('Some error');
        $this->client->request(['url' => '/projects']);
    }

    public function testThrowsOnInvalidDate()
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Invalid ISO-date');
        $this->client = new Client([
            'projectId' => 'abc',
            'dataset' => 'production',
            'apiVersion' => '2018-14-03',
        ]);
    }

    public function testThrowsOnInvalidApiVersion()
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Invalid API version');
        $this->client = new Client([
            'projectId' => 'abc',
            'dataset' => 'production',
            'apiVersion' => '3',
        ]);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testDoesNotThrowOnExperimentalApiVersion()
    {
        new Client([
            'projectId' => 'abc',
            'dataset' => 'production',
            'apiVersion' => 'X',
        ]);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testDoesNotThrowOnApiVersionOne()
    {
        new Client([
            'projectId' => 'abc',
            'dataset' => 'production',
            'apiVersion' => '1',
        ]);
    }

    public function testThrowsWhenConstructingNewClientWithTokenAndCdnOption()
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage(
            'Cannot combine `useCdn` option with `token` as authenticated requests cannot be cached'
        );
        $this->client = new Client([
            'projectId' => 'abc',
            'dataset' => 'production',
            'useCdn' => true,
            'token' => 'foo',
            'apiVersion' => '2019-01-01',
        ]);
    }

    public function testThrowsWhenConstructingClientWithoutProjectId()
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Configuration must contain `projectId`');
        $this->client = new Client([
            'dataset' => 'production',
            'apiVersion' => '2019-01-01',
        ]);
    }

    public function testThrowsWhenConstructingClientWithoutDataset()
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Configuration must contain `dataset`');
        $this->client = new Client(['projectId' => 'abc', 'apiVersion' => '2019-01-01']);
    }

    public function testCanSetAndGetConfig()
    {
        $this->client = new Client([
            'projectId' => 'abc',
            'dataset' => 'production',
            'apiVersion' => '2019-01-01',
        ]);
        $this->assertEquals('production', $this->client->config()['dataset']);
        $this->assertEquals($this->client, $this->client->config(['dataset' => 'staging']));
        $this->assertEquals('staging', $this->client->config()['dataset']);
    }

    public function testCanCreateProjectlessClient()
    {
        $mockBody = ['some' => 'response'];

        $this->history = [];
        $historyMiddleware = Middleware::history($this->history);

        $stack = HandlerStack::create(new MockHandler([$this->mockJsonResponseBody($mockBody)]));
        $stack->push($historyMiddleware);

        $this->client = new Client([
            'useProjectHostname' => false,
            'handler' => $stack,
            'token' => 'mytoken',
            'apiVersion' => '2019-01-22',
        ]);

        $response = $this->client->request(['url' => '/projects']);
        $this->assertEquals($mockBody, $response);
    }

    public function testCanGetDocument()
    {
        $expected = ['_id' => 'someDocId', '_type' => 'bike', 'name' => 'Tandem Extraordinaire'];
        $mockBody = ['documents' => [$expected]];
        $this->mockResponses([$this->mockJsonResponseBody($mockBody)], ['apiVersion' => '2019-01-20']);

        $this->assertEquals($expected, $this->client->getDocument('someDocId'));
        $this->assertPreviousRequest(['url' => 'https://abc.api.sanity.io/v2019-01-20/data/doc/production/someDocId']);
        $this->assertPreviousRequest(['headers' => ['Authorization' => 'Bearer muchsecure']]);
    }

    public function testCanGetDocumentFromCdn()
    {
        $expected = ['_id' => 'someDocId', '_type' => 'bike', 'name' => 'Tandem Extraordinaire'];
        $mockBody = ['documents' => [$expected]];
        $this->mockResponses([$this->mockJsonResponseBody($mockBody)], ['useCdn' => true, 'token' => null]);

        $this->assertEquals($expected, $this->client->getDocument('someDocId'));
        $this->assertPreviousRequest(['url' => 'https://abc.apicdn.sanity.io/v2019-01-01/data/doc/production/someDocId']);
    }

    public function testIncludesUserAgent()
    {
        $expected = ['_id' => 'someDocId', '_type' => 'bike', 'name' => 'Tandem Extraordinaire'];
        $mockBody = ['documents' => [$expected]];
        $this->mockResponses([$this->mockJsonResponseBody($mockBody)]);

        $this->assertEquals($expected, $this->client->getDocument('someDocId'));
        $this->assertPreviousRequest(['url' => 'https://abc.api.sanity.io/v2019-01-01/data/doc/production/someDocId']);
        $this->assertPreviousRequest(['headers' => ['User-Agent' => 'sanity-php ' . Version::VERSION]]);
    }

    public function testThrowsServerExceptionOn5xxErrors()
    {
        $mockBody = ['error' => 'SomeError', 'message' => 'Server returned some error'];
        $this->mockResponses([$this->mockJsonResponseBody($mockBody, 500)]);
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('SomeError - Server returned some error');
        $this->client->getDocument('someDocId');
    }

    public function testCanQueryForDocumentsWithoutParams()
    {
        $query = '*[seats >= 2]';
        $expected = [['_id' => 'someDocId', '_type' => 'bike', 'name' => 'Tandem Extraordinaire', 'seats' => 2]];
        $mockBody = ['result' => $expected];
        $this->mockResponses([$this->mockJsonResponseBody($mockBody)]);

        $this->assertEquals($expected, $this->client->fetch($query));
        $this->assertPreviousRequest([
            'url' => 'https://abc.api.sanity.io/v2019-01-01/data/query/production?query=%2A%5Bseats%20%3E%3D%202%5D',
            'headers' => ['Authorization' => 'Bearer muchsecure'],
        ]);
    }

    public function testCanQueryForDocumentsWithParams()
    {
        $expected = [['_id' => 'someDocId', '_type' => 'bike', 'name' => 'Tandem Extraordinaire', 'seats' => 2]];
        $mockBody = ['result' => $expected];
        $this->mockResponses([$this->mockJsonResponseBody($mockBody)]);

        $query = '*[seats >= $minSeats]';
        $params = ['minSeats' => 2];

        $expectedUrl = 'https://abc.api.sanity.io/v2019-01-01/data/query/production?';
        $expectedUrl .= 'query=%2A%5Bseats%20%3E%3D%20%24minSeats%5D&%24minSeats=2';

        $this->assertEquals($expected, $this->client->fetch($query, $params));
        $this->assertPreviousRequest([
            'url' => $expectedUrl,
            'headers' => ['Authorization' => 'Bearer muchsecure'],
        ]);
    }

    public function testCanQueryForDocumentsThroughAlias()
    {
        $query = '*[seats >= 2]';
        $expected = [['_id' => 'someDocId', '_type' => 'bike', 'name' => 'Tandem Extraordinaire', 'seats' => 2]];
        $mockBody = ['result' => $expected];
        $this->mockResponses([$this->mockJsonResponseBody($mockBody)], ['dataset' => '~current']);

        $this->assertEquals($expected, $this->client->fetch($query));
        $this->assertPreviousRequest([
            'url' => 'https://abc.api.sanity.io/v2019-01-01/data/query/~current?query=%2A%5Bseats%20%3E%3D%202%5D',
            'headers' => ['Authorization' => 'Bearer muchsecure'],
        ]);
    }

    public function testCanQueryForDocumentsWithoutFilteringResponse()
    {
        $query = '*[seats >= 2]';
        $results = [['_id' => 'someDocId', '_type' => 'bike', 'name' => 'Tandem Extraordinaire', 'seats' => 2]];
        $mockBody = ['result' => $results];
        $this->mockResponses([$this->mockJsonResponseBody($mockBody)]);

        $this->assertEquals($mockBody, $this->client->fetch($query, null, ['filterResponse' => false]));
        $this->assertPreviousRequest([
            'url' => 'https://abc.api.sanity.io/v2019-01-01/data/query/production?query=%2A%5Bseats%20%3E%3D%202%5D',
            'headers' => ['Authorization' => 'Bearer muchsecure'],
        ]);
    }

    public function testCanQueryForDocumentsFromCdn()
    {
        $query = '*[seats >= 2]';
        $expected = [['_id' => 'someDocId', '_type' => 'bike', 'name' => 'Tandem Extraordinaire', 'seats' => 2]];
        $mockBody = ['result' => $expected];
        $this->mockResponses([$this->mockJsonResponseBody($mockBody)], ['useCdn' => true, 'token' => null]);

        $this->assertEquals($expected, $this->client->fetch($query));
        $this->assertPreviousRequest([
            'url' => 'https://abc.apicdn.sanity.io/v2019-01-01/data/query/production?query=%2A%5Bseats%20%3E%3D%202%5D'
        ]);
    }

    public function testThrowsClientExceptionOn4xxErrors()
    {
        $mockBody = ['error' => [
            'description' => 'Param $minSeats referenced, but not provided',
            'type' => 'queryParseError'
        ]];
        $this->mockResponses([$this->mockJsonResponseBody($mockBody, 400)]);
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Param $minSeats referenced, but not provided');
        $this->client->fetch('*[seats >= $minSeats]');
    }

    public function testCanCreateDocument()
    {
        $document = ['_type' => 'bike', 'seats' => 12, 'name' => 'Dusinsykkel'];
        $result = ['_id' => 'someNewDocId'] + $document;
        $mockBody = ['results' => [['id' => 'someNewDocId', 'document' => $result]]];
        $this->mockResponses([$this->mockJsonResponseBody($mockBody)]);

        $this->assertEquals($result, $this->client->create($document));
        $this->assertPreviousRequest([
            'url' => 'https://abc.api.sanity.io/v2019-01-01/data/mutate/production?returnIds=true&returnDocuments=true',
            'headers' => ['Authorization' => 'Bearer muchsecure'],
            'requestBody' => json_encode(['mutations' => [['create' => $document]]])
        ]);
    }

    public function testDoesNotUseCdnForMutations()
    {
        $document = ['_type' => 'bike', 'seats' => 12, 'name' => 'Dusinsykkel'];
        $result = ['_id' => 'someNewDocId'] + $document;
        $mockBody = ['results' => [['id' => 'someNewDocId', 'document' => $result]]];
        $this->mockResponses([$this->mockJsonResponseBody($mockBody)], ['useCdn' => true, 'token' => null]);

        $this->assertEquals($result, $this->client->create($document));
        $this->assertPreviousRequest([
            'url' => 'https://abc.api.sanity.io/v2019-01-01/data/mutate/production?returnIds=true&returnDocuments=true',
            'requestBody' => json_encode(['mutations' => [['create' => $document]]])
        ]);
    }

    public function testThrowsWhenCreatingDocumentWithoutType()
    {
        $this->mockResponses([]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/properties are missing: _type$/');
        $this->client->create(['foo' => 'bar']);
    }

    public function testCanRunMutationsAndReturnFirstIdOnly()
    {
        $document = ['_type' => 'bike', 'seats' => 12, 'name' => 'Dusinsykkel'];
        $mutations = [['create' => $document]];
        $result = ['_id' => 'someNewDocId'] + $document;
        $mockBody = [
            'transactionId' => 'foo',
            'results' => [['id' => 'someNewDocId', 'document' => $result]],
            'documentId' => 'someNewDocId',
        ];
        $this->mockResponses([$this->mockJsonResponseBody($mockBody)]);

        $this->assertEquals($mockBody, $this->client->mutate($mutations, [
            'returnFirst' => true
        ]));

        $this->assertPreviousRequest([
            'url' => 'https://abc.api.sanity.io/v2019-01-01/data/mutate/production?returnIds=true&returnDocuments=true',
            'headers' => ['Authorization' => 'Bearer muchsecure'],
            'requestBody' => json_encode(['mutations' => $mutations])
        ]);
    }

    public function testMutateWillSerializePatchInstance()
    {
        $document = ['_id' => 'someDocId', '_type' => 'someType', 'count' => 2];
        $mockBody = ['transactionId' => 'poc', 'results' => [['id' => 'someDocId', 'document' => $document]]];
        $this->mockResponses([$this->mockJsonResponseBody($mockBody)]);

        $patch = $this->client->patch('someDocId')->inc(['count' => 1]);
        $this->client->mutate($patch);

        $this->assertPreviousRequest([
            'url' => 'https://abc.api.sanity.io/v2019-01-01/data/mutate/production?returnIds=true&returnDocuments=true',
            'requestBody' => json_encode(['mutations' => [['patch' => $patch->serialize()]]])
        ]);
    }

    public function testMutateWillSerializeTransactionInstance()
    {
        $document = ['_id' => 'someDocId', '_type' => 'someType', 'count' => 2];
        $mockBody = ['transactionId' => 'poc', 'results' => [['id' => 'someDocId', 'document' => $document]]];
        $this->mockResponses([$this->mockJsonResponseBody($mockBody)]);

        $transaction = $this->client->transaction()->patch('someDocId', ['count' => 1]);
        $this->client->mutate($transaction);

        $this->assertPreviousRequest([
            'url' => 'https://abc.api.sanity.io/v2019-01-01/data/mutate/production?returnIds=true&returnDocuments=true',
            'requestBody' => json_encode(['mutations' => $transaction->serialize()])
        ]);
    }

    public function testCanCreateDocumentWithVisibilityOption()
    {
        $document = ['_type' => 'bike', 'seats' => 12, 'name' => 'Dusinsykkel'];
        $result = ['_id' => 'someNewDocId'] + $document;
        $mockBody = ['results' => [['id' => 'someNewDocId', 'document' => $result]]];
        $this->mockResponses([$this->mockJsonResponseBody($mockBody)]);

        $this->assertEquals($result, $this->client->create($document, ['visibility' => 'async']));
        $this->assertPreviousRequest([
            'url' => 'https://abc.api.sanity.io/v2019-01-01/data/mutate/production?returnIds=true&returnDocuments=true&visibility=async',
            'headers' => ['Authorization' => 'Bearer muchsecure'],
            'requestBody' => json_encode(['mutations' => [['create' => $document]]])
        ]);
    }

    public function testCanCreateDocumentIfNotExists()
    {
        $document = ['_id' => 'foobar', '_type' => 'bike', 'seats' => 12, 'name' => 'Dusinsykkel'];
        $mockBody = ['results' => [['id' => 'foobar', 'document' => $document]]];
        $this->mockResponses([$this->mockJsonResponseBody($mockBody)]);

        $this->assertEquals($document, $this->client->createIfNotExists($document));
        $this->assertPreviousRequest([
            'url' => 'https://abc.api.sanity.io/v2019-01-01/data/mutate/production?returnIds=true&returnDocuments=true',
            'headers' => ['Authorization' => 'Bearer muchsecure'],
            'requestBody' => json_encode(['mutations' => [['createIfNotExists' => $document]]])
        ]);
    }

    public function testThrowsWhenCallingCreateIfNotExistsWithoutId()
    {
        $this->mockResponses([]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/properties are missing: _id$/');
        $this->client->createIfNotExists(['_type' => 'bike']);
    }

    public function testCanCreateOrReplaceDocument()
    {
        $document = ['_id' => 'foobar', '_type' => 'bike', 'seats' => 12, 'name' => 'Dusinsykkel'];
        $mockBody = ['results' => [['id' => 'foobar', 'document' => $document]]];
        $this->mockResponses([$this->mockJsonResponseBody($mockBody)]);

        $this->assertEquals($document, $this->client->createOrReplace($document));
        $this->assertPreviousRequest([
            'url' => 'https://abc.api.sanity.io/v2019-01-01/data/mutate/production?returnIds=true&returnDocuments=true',
            'headers' => ['Authorization' => 'Bearer muchsecure'],
            'requestBody' => json_encode(['mutations' => [['createOrReplace' => $document]]])
        ]);
    }

    public function testThrowsWhenCallingCreateOrReplaceWithoutId()
    {
        $this->mockResponses([]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/properties are missing: _id$/');
        $this->client->createOrReplace(['_type' => 'bike']);
    }

    public function testCanGeneratePatch()
    {
        $this->client = new Client([
            'projectId' => 'abc',
            'dataset' => 'production',
            'apiVersion' => '2019-01-01',
        ]);
        $this->assertInstanceOf(Patch::class, $this->client->patch('someDocId'));
    }

    public function testCanGeneratePatchWithInitialOperations()
    {
        $this->client = new Client([
            'projectId' => 'abc',
            'dataset' => 'production',
            'apiVersion' => '2019-01-01',
        ]);
        $serialized = $this->client->patch('someDocId', ['inc' => ['seats' => 1]])->serialize();
        $this->assertEquals(['id' => 'someDocId', 'inc' => ['seats' => 1]], $serialized);
    }

    public function testCanCommitPatch()
    {
        $document = ['_id' => 'someDocId', '_type' => 'bike', 'seats' => 2];
        $mockBody = ['results' => [['id' => 'someDocId', 'document' => $document]]];
        $this->mockResponses([$this->mockJsonResponseBody($mockBody)]);
        $newDoc = $this->client
            ->patch('someDocId', ['inc' => ['seats' => 1]])
            ->setIfMissing(['seats' => 1])
            ->commit();

        $this->assertEquals($document, $newDoc);
        $this->assertPreviousRequest([
            'url' => 'https://abc.api.sanity.io/v2019-01-01/data/mutate/production?returnIds=true&returnDocuments=true',
            'headers' => ['Authorization' => 'Bearer muchsecure'],
            'requestBody' => json_encode(['mutations' => [['patch' => [
                'id' => 'someDocId',
                'inc' => ['seats' => 1],
                'setIfMissing' => ['seats' => 1]
            ]]]])
        ]);
    }

    public function testCanGenerateTransaction()
    {
        $this->client = new Client([
            'projectId' => 'abc',
            'dataset' => 'production',
            'apiVersion' => '2019-01-01',
        ]);
        $this->assertInstanceOf(Transaction::class, $this->client->transaction());
    }

    public function testCanGenerateTransactionWithInitialOperations()
    {
        $this->client = new Client([
            'projectId' => 'abc',
            'dataset' => 'production',
            'apiVersion' => '2019-01-01',
        ]);
        $serialized = $this->client->transaction([['create' => ['_type' => 'bike']]])->serialize();
        $this->assertEquals([['create' => ['_type' => 'bike']]], $serialized);
    }

    public function testCanCommitTransaction()
    {
        $mockBody = ['transactionId' => 'moo', 'results' => [['id' => 'someNewDocId', 'operation' => 'create']]];
        $this->mockResponses([$this->mockJsonResponseBody($mockBody)]);
        $result = $this->client
            ->transaction([['create' => ['_type' => 'bike']]])
            ->commit();

        $expected = $mockBody + ['documentIds' => ['someNewDocId']];
        $this->assertEquals($expected, $result);
        $this->assertPreviousRequest([
            'url' => 'https://abc.api.sanity.io/v2019-01-01/data/mutate/production?returnIds=true',
            'headers' => ['Authorization' => 'Bearer muchsecure'],
            'requestBody' => json_encode(['mutations' => [['create' => [
                '_type' => 'bike'
            ]]]])
        ]);
    }

    public function testCanHaveTransactionDocumentsReturned()
    {
        $results = [
            ['id' => '123', 'document' => ['_id' => '123', '_type' => 'bike', 'title' => 'Tandem']],
            ['id' => '456', 'document' => ['_id' => '456', '_type' => 'bike', 'title' => 'City Bike']]
        ];
        $mockBody = ['transactionId' => 'moo', 'results' => $results];
        $mutations = [
            ['create' => ['_type' => 'bike', 'title' => 'Tandem']],
            ['create' => ['_type' => 'bike', 'title' => 'City Bike']]
        ];

        $this->mockResponses([$this->mockJsonResponseBody($mockBody)]);
        $result = $this->client
            ->transaction($mutations)
            ->commit(['returnDocuments' => true]);

        $expected = ['123' => $results[0]['document'], '456' => $results[1]['document']];
        $this->assertEquals($expected, $result);
        $this->assertPreviousRequest([
            'url' => 'https://abc.api.sanity.io/v2019-01-01/data/mutate/production?returnIds=true&returnDocuments=true',
            'headers' => ['Authorization' => 'Bearer muchsecure'],
            'requestBody' => json_encode(['mutations' => $mutations])
        ]);
    }

    public function testCanDeleteDocument()
    {
        $mockBody = ['transactionId' => 'fnatt', 'results' => [['id' => 'foobar', 'operation' => 'delete']]];
        $this->mockResponses([$this->mockJsonResponseBody($mockBody)]);

        $expected = $mockBody + ['documentIds' => ['foobar']];
        $this->assertEquals($expected, $this->client->delete('foobar'));
        $this->assertPreviousRequest([
            'url' => 'https://abc.api.sanity.io/v2019-01-01/data/mutate/production?returnIds=true',
            'headers' => ['Authorization' => 'Bearer muchsecure'],
            'requestBody' => json_encode(['mutations' => [['delete' => ['id' => 'foobar']]]])
        ]);
    }

    public function testResolvesErrorMessageFromNonStandardResponseWithOnlyError()
    {
        $mockBody = ['error' => 'Some error message'];
        $this->mockResponses([$this->mockJsonResponseBody($mockBody, 500)]);
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Some error message');
        $this->client->getDocument('someDocId');
    }

    public function testResolvesErrorMessageFromNonStandardResponseWithOnlyMessage()
    {
        $mockBody = ['message' => 'Some error message'];
        $this->mockResponses([$this->mockJsonResponseBody($mockBody, 500)]);
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Some error message');
        $this->client->getDocument('someDocId');
    }

    public function testResolvesErrorMessageFromNonStandardResponse()
    {
        $mockBody = ['some' => 'thing'];
        $this->mockResponses([$this->mockJsonResponseBody($mockBody, 500)]);
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Unknown error; body: {"some":"thing"}');
        $this->client->getDocument('someDocId');
    }

    public function testCanGetResponseFromRequestException()
    {
        $this->mockResponses([$this->mockJsonResponseBody(['some' => 'thing'], 500)]);
        try {
            $this->client->getDocument('someDocId');
        } catch (ServerException $error) {
            $body = (string) $error->getResponse()->getBody();
            $this->assertEquals(json_encode(['some' => 'thing']), $body);
            $this->assertEquals(json_encode(['some' => 'thing']), $error->getResponseBody());
            $this->assertEquals(500, $error->getStatusCode());
        }
    }

    public function testThrowsOnInvalidSelections()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/^Invalid selection/');
        new Selection(['foo' => 'bar']);
    }

    public function testCanSerializeQuerySelection()
    {
        $sel = new Selection(['query' => '*']);
        $this->assertEquals(['query' => '*'], $sel->serialize());
    }

    public function testCanSerializeMultiIdSelection()
    {
        $sel = new Selection(['abc', '123']);
        $this->assertEquals(['id' => ['abc', '123']], $sel->serialize());
    }

    public function testCanSerializeSingleIdSelection()
    {
        $sel = new Selection('abc123');
        $this->assertEquals(['id' => 'abc123'], $sel->serialize());
    }

    public function testCanJsonEncodeSelection()
    {
        $sel = new Selection('abc123');
        $this->assertEquals(json_encode(['id' => 'abc123']), json_encode($sel));
    }

    /**
     * Helpers
     */
    private function mockResponses($mocks, $clientOptions = [])
    {
        $this->history = [];
        $historyMiddleware = Middleware::history($this->history);

        $stack = HandlerStack::create(new MockHandler($mocks));
        $stack->push($historyMiddleware);

        $this->initClient($stack, $clientOptions);
    }

    private function initClient($stack = null, $clientOptions = [])
    {
        $this->client = new Client(array_merge([
            'projectId' => 'abc',
            'dataset' => 'production',
            'token' => 'muchsecure',
            'apiVersion' => '2019-01-01',
            'handler' => $stack,
        ], $clientOptions));
    }

    private function mockJsonResponseBody($body, $statusCode = 200, $headers = [])
    {
        return new Response($statusCode, array_merge(['Content-Type' => 'application/json'], $headers), json_encode($body));
    }

    private function assertRequest($expected, $request)
    {
        if (isset($expected['url'])) {
            $this->assertEquals($expected['url'], (string) $request['request']->getUri());
        }

        if (isset($expected['headers'])) {
            foreach ($expected['headers'] as $header => $value) {
                $this->assertEquals($value, $request['request']->getHeaderLine($header));
            }
        }

        if (isset($expected['requestBody'])) {
            $this->assertEquals($expected['requestBody'], (string) $request['request']->getBody());
        }
    }

    private function assertPreviousRequest($expected)
    {
        $this->assertRequest($expected, $this->history[0]);
    }
}
