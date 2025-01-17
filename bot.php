 <?php

/**
 * Copyright 2023 bariscodefx
 * 
 * This file part of project Hiro 016 Discord Bot.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

include __DIR__ . '/vendor/autoload.php';

use hiro\Hiro;
use Discord\Parts\User\Activity;
use hiro\CommandLoader;
use hiro\ArgumentParser;
use hiro\PresenceManager;
use Discord\WebSockets\Intents;
use hiro\Version;

if ( !isset( $_ENV['TOKEN'] ) ) {
	$dotenv = Dotenv\Dotenv::createImmutable("./");
	$dotenv->load();
}

if ( Version::TYPE == 'development' )
{
    error_reporting(E_ALL);
    @ini_set('display_errors', 'On');
}

$ArgumentParser = new ArgumentParser($argv);
$shard_id = $ArgumentParser->getShardId();
$shard_count = $ArgumentParser->getShardCount();
$bot = new Hiro([
    'token' => $_ENV['TOKEN'],
    'prefix' => $_ENV['PREFIX'],
    'shardId' => $shard_id,
    'shardCount' => $shard_count,
    'caseInsensitiveCommands' => true,
    'loadAllMembers' => true,
    'intents' => Intents::getDefaultIntents() | Intents::GUILD_MEMBERS | Intents::MESSAGE_CONTENT
]);
$voiceClients = [];

function getPresenceState(): ?array
{
	global $bot, $shard_id, $shard_count;
	return [
        "{$_ENV['PREFIX']}help | " . $bot->formatNumber(sizeof($bot->guilds)) . " guilds | Shard " . $shard_id  + 1 . " of $shard_count",
        "⚔️ RPG System coming soon!",
        "🎶 Music system is working!"
    ];
}

$bot->on('ready', function($discord) use ($shard_id, $shard_count) {
    $discord->logger->pushHandler(new \Monolog\Handler\StreamHandler('php://stdout', \Monolog\Level::Info));
    echo "Bot is ready!", PHP_EOL;
    
    $commandLoader = new CommandLoader($discord);

    $presenceManager = new PresenceManager($discord);
    $presenceManager->setLoopTime(15.0)
    ->setPresenceType(Activity::TYPE_WATCHING)
    ->setPresences(getPresenceState())
    ->startThread();

    /** fix discord guild count */
    $discord->getLoop()->addPeriodicTimer($presenceManager->looptime, function() use ($presenceManager, $discord, $shard_id, $shard_count)
    {
        $presenceManager->setPresences(getPresenceState());
    });

});

$bot->run();
