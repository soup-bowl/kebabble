<?php namespace kebabble\slack;

defined( 'ABSPATH' ) or die( 'Operation not permitted.' );

use Carbon\Carbon;

class formatting {
	protected $slogans;
	
	public function __construct() {
		$tax = get_option('kbfos_settings')["kbfos_drivertax"];
		$this->slogans = [
			"*Additional {$tax}p per person* to fund the driver. Tax evaders will be taken to Crown court :crown:",
			'Kebab\'o\'clock ~1pm, so orders in by 12pm :clock12: No mercy shown for missed orders.'
		];
	}
	
	/**
	 * Makes a kebabble menu listing status.
	 * @param string $food Dictates header. e.g. Kebab Mondays.
	 * @param string $orders Displayed monospaced.
	 * @param string $driver Driver name.
	 * @param Carbon $date Date of order, typically today.
	 * @param array[string] $payments Payment methods accepted.
	 * @return string
	 */
	public function status($food, $order, $driver, $date = false, $payments = ["Cash"]) {
		$rolls    = ($rolls == "")    ? "N/A"         : $rolls;
		$order    = ($order == "")    ? "N/A"         : $order;
		$date     = ($date == false)  ? Carbon::now() : $date;
		$evMoji   = $this->emojiPicker($food);
		
		$formattedString = "";
		$formattedPosts = [
			"{$evMoji} *{$food} {$date->format('l')} ({$date->format('jS F')})* {$evMoji}",
			"*Orders*```{$order}```\n(MD = Meal Deal - see pinned)",
			/*Order of *not implemented* pieces totalling *not implemented*, with a *not implemented* tax.*/ "Polling @channel for orders. Today's driver is *{$driver}* :car:",
			$this->slogans[0],
			$this->slogans[1],
			$this->acceptsPaymentFormatter($payments)
		];

		foreach ($formattedPosts as $formattedPost) {
			$formattedString .= $formattedPost . "\n\n";
		}
		
		return $formattedString;
	}

	/**
	 * Returns a 'Driver accepts...' string with the given array displayed.
	 * @param array $acceptedPayments
	 * @return string
	 */
	private function acceptsPaymentFormatter($acceptedPayments) {
		$formatString = "Driver accepts ";

		if (count($acceptedPayments) !== 1) {
			for ($i = 0; $i < count($acceptedPayments); $i++) { 
				if (($i + 1) == count($acceptedPayments)) {
					$formatString .= "& {$acceptedPayments[$i]}.";
				} else {
					$formatString .= "{$acceptedPayments[$i]}, ";
				}
			}
		} else {
			$formatString .= "{$acceptedPayments[0]}.";
		}
		return $formatString;
	}
	
	/**
	 * Chooses the emoji for the food type... yep, this exists.
	 * @param string $food
	 * @return string Shortcode-formatted emoji
	 */
	private function emojiPicker($food) {
		switch ( strtolower($food) ) {
			case "burger":
				return ":hamburger:";
			case "pizza":
				return ":pizza:";
			case "event":
				return ":popcorn:";
			default:
				return ":burrito:";
		}
	}
}