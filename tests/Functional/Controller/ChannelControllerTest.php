<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Enum\ChannelTypeEnum;
use App\Factory\ChannelFactory;
use App\Factory\ServerFactory;
use App\Factory\UserFactory;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class ChannelControllerTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    public function test_it_redirects_unauthenticated_user_to_login(): void
    {
        // Arrange
        $client = ChannelControllerTest::createClient();
        $server = ServerFactory::createOne();

        // Act
        $client->request('GET', sprintf('/servers/%s/channels', $server->getId()->toRfc4122()));

        // Assert
        $this->assertResponseRedirects('/login');
    }

    public function test_it_denies_access_for_user_without_server_permissions(): void
    {
        // Arrange
        $client = ChannelControllerTest::createClient();
        $serverOwner = UserFactory::createOne();
        $server = ServerFactory::createOne(['owner' => $serverOwner]);
        $unauthorizedUser = UserFactory::createOne();
        $client->loginUser($unauthorizedUser->_real());

        // Act
        $client->request('GET', sprintf('/servers/%s/channels/create', $server->getId()->toRfc4122()));

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function test_default_channel_redirects_to_first_available_channel(): void
    {
        // Arrange
        $client = ChannelControllerTest::createClient();
        $serverOwner = UserFactory::createOne();
        $server = ServerFactory::createOne(['owner' => $serverOwner]);
        $client->loginUser($serverOwner->_real());

        ChannelFactory::createOne([
            'server' => $server,
            'type' => ChannelTypeEnum::VOICE,
            'createdAt' => new DateTime('-5 days')
        ]);

        $targetChannel = $server->getChannels()->first();
        $this->assertNotFalse($targetChannel, 'Target channel not found.');

        ChannelFactory::createOne([
            'server' => $server,
            'type' => ChannelTypeEnum::TEXT,
            'createdAt' => new DateTime('-5 days')
        ]);

        // Act
        $client->request('GET', sprintf('/servers/%s/channels', $server->getId()->toRfc4122()));

        // Assert
        $this->assertResponseRedirects(sprintf(
            '/servers/%s/channels/%s',
            $server->getId()->toRfc4122(),
            $targetChannel->getId()->toRfc4122()
        ));
    }

    public function test_channel_view_renders_successfully_and_sets_mercure_cookie(): void
    {
        // Arrange
        $client = ChannelControllerTest::createClient([], [
            'HTTP_HOST' => 'localhost'
        ]);
        $serverOwner = UserFactory::createOne();
        $server = ServerFactory::createOne(['owner' => $serverOwner]);
        $channel = ChannelFactory::repository()->findBy(['server' => $server->_real()])[0];

        $client->loginUser($serverOwner->_real());

        // Act
        $client->request('GET', sprintf(
            '/servers/%s/channels/%s',
            $server->getId()->toRfc4122(),
            $channel->getId()->toRfc4122()
        ));

        // Assert
        $this->assertResponseIsSuccessful();

        $responseCookies = $client->getResponse()->headers->getCookies();
        $mercureCookieFound = false;


        foreach ($responseCookies as $cookie) {
            if ($cookie->getName() === 'mercureAuthorization') {
                $mercureCookieFound = true;
                break;
            }
        }

        $this->assertTrue($mercureCookieFound, 'The application did not generate the mercureAuthorization cookie in the response headers!');
    }

    public function test_it_submits_create_channel_form_successfully(): void
    {
        // Arrange
        $client = ChannelControllerTest::createClient();
        $serverOwner = UserFactory::createOne();
        $server = ServerFactory::createOne(['owner' => $serverOwner]);

        $client->loginUser($serverOwner->_real());
        $client->request('GET', sprintf('/servers/%s/channels/create', $server->getId()->toRfc4122()));

        // Act
        $client->submitForm('Stwórz kanał', [
            'create_channel[name]' => 'nowy-super-kanal',
        ]);

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_SEE_OTHER);

        ChannelFactory::repository()->assert()->count(2);
        $savedChannel = ChannelFactory::repository()->findOneBy(['name' => 'nowy-super-kanal']);
        $this->assertNotNull($savedChannel, 'The channel was not saved to the database.');
        $this->assertSame('nowy-super-kanal', $savedChannel->getName());

        $session = $client->getRequest()->getSession();
        $flashes = $session->getFlashBag()->peek('success');

        $this->assertNotEmpty($flashes, 'No "success" flash message found in the session after creating the channel.');
        $this->assertContains('Pomyślnie utworzono nowy kanał.', $flashes);
    }

    public function test_it_deletes_channel_with_valid_csrf_token(): void
    {
        // Arrange
        $client = ChannelControllerTest::createClient();
        $serverOwner = UserFactory::createOne();
        $server = ServerFactory::createOne(['owner' => $serverOwner]);
        $channelToDelete = ChannelFactory::repository()->findBy(['server' => $server->_real()])[0];
        ChannelFactory::createOne(['server' => $server]);
        $client->loginUser($serverOwner->_real());

        $crawler = $client->request('GET', sprintf(
            '/servers/%s/channels/%s/edit',
            $server->getId()->toRfc4122(),
            $channelToDelete->getId()->toRfc4122()
        ));

        $csrfToken = $crawler->filter('input[name="_csrf_token"]')->attr('value');

        // Act
        $client->request(
            'POST',
            sprintf('/servers/%s/channels/%s/delete', $server->getId()->toRfc4122(), $channelToDelete->getId()->toRfc4122()),
            ['_csrf_token' => $csrfToken]
        );

        // Assert
        $this->assertResponseRedirects(sprintf('/servers/%s/channels', $server->getId()->toRfc4122()));
        ChannelFactory::repository()->assert()->notExists(['id' => $channelToDelete->getId()]);

        $session = $client->getRequest()->getSession();
        $flashes = $session->getFlashBag()->peek('success');

        $this->assertNotEmpty($flashes, 'No "success" flash message found in the session after deleting the channel.');
        $this->assertContains('Pomyślnie usunięto kanał.', $flashes);
    }

    public function test_it_prevents_deletion_with_invalid_csrf_token(): void
    {
        // Arrange
        $client = ChannelControllerTest::createClient();
        $client->followRedirects();
        $serverOwner = UserFactory::createOne();
        $server = ServerFactory::createOne(['owner' => $serverOwner]);
        $channel = ChannelFactory::repository()->findBy(['server' => $server->_real()])[0];
        $client->loginUser($serverOwner->_real());

        // Act
        $client->request(
            'POST',
            sprintf('/servers/%s/channels/%s/delete', $server->getId()->toRfc4122(), $channel->getId()->toRfc4122()),
            ['_csrf_token' => 'invalid_token_123']
        );

        // Assert
        ChannelFactory::repository()->assert()->exists(['id' => $channel->getId()]);
        $this->assertSelectorTextContains('.toast-body', 'Nieprawidłowy token bezpieczeństwa. Spróbuj ponownie.');
    }
}
