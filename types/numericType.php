<?

class numericType extends textType {
	public $validation = 'float';
	public $decimals = 2;

	public function toString() {
		return $this->escape(number_format($this->value, $this->decimals, '.', ' '));
	}

	public function toListElement() {
		return "<div class='text-right'>".$this->toStringTruncated()."</div>";
	}	
	public function toListItem() {
		return "<div class='text-right'>".$this->toStringTruncated()."</div>";
	}	
	
}