<?php

namespace App\Search;

use RuntimeException;

/** An evaluation asked for an engine that could not run, so its numbers would really be another engine's. */
class EngineUnavailable extends RuntimeException {}
