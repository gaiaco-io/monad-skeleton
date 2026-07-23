<?php

/** @var string $csrf_token */

use Gaia\Clarity\Middlewares\MetaTag;

$layout = 'Layouts/main';

MetaTag::set(['title' => 'Welcome', 'description' => 'A Monad Framework application.']);
?>

<div class="text-center py-10 px-4 sm:px-6 lg:px-8">
    <h1 class="text-4xl my-6 font-bold">Welcome to Monad</h1>
    <p class="text-xl font-normal leading-relaxed my-4">Necessitate only the necessary</p>
    <p class="text-xl font-thin leading-relaxed my-4">
        Monad is a modern and lightweight framework for building applications in PHP.
        Deep scaffolding is shun away in Monad.
    </p>
    <p class="my-2">
        <a class="font-mono font-thin underline" href="/users">See the example Users page &rarr;</a>
    </p>
</div>
