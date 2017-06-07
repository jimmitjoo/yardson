<?php

namespace BrandsSync\Models;

class Remote_Order {
	const TABLE_NAME = 'brandssync_remote_orders';

	const STATUS_FAILED = 2000;
	const STATUS_NOAVAILABILITY = 2001;
	const STATUS_BOOKED = 5;
	const STATUS_CONFIRMED = 2;
	const STATUS_WORKING_ON = 3001;
	const STATUS_READY = 3002;
	const STATUS_DISPATCHED = 3;
}
