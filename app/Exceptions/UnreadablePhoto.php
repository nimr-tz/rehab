<?php

namespace App\Exceptions;

use RuntimeException;

/** An upload that cannot go into the gallery. The message is shown to the photographer. */
class UnreadablePhoto extends RuntimeException {}
