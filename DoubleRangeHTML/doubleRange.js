class CustomRange {
    constructor(container, min, max, defaultMin, defaultMax) {
        this._container = container;
        this._bar = document.createElement("DIV");
        this._activeThumb = null;
        this._thumb1 = document.createElement("DIV");
        this._thumb2 = document.createElement("DIV");
        this.isSliding = false;
        this._rangeMin = min;
        this._rangeMax = max;
        this.minValue = defaultMin;
        this.maxValue = defaultMax;
        this._ratio = 0;
        this.thumbHalfWidth = 0;
        this.events = new EventTarget();
        this.clicked = false;

        this._bar.className = "range-bar";
        this._thumb1.className = "thumb";
        this._thumb1.dataset.isMinSlider = true;
        this._thumb1.dataset.curPos = min;

        this._thumb2.className = "thumb";
        this._thumb2.dataset.isMinSlider = false;
        this._thumb2.dataset.curPos = max;

        this._bar.append(this._thumb1);
        this._bar.append(this._thumb2);
        this._container.append(this._bar);

        this._ratio = this._bar.getBoundingClientRect().width / (this._rangeMax - this._rangeMin);
        this.thumbHalfWidth = this._thumb1.getBoundingClientRect().width / 2;
        this.SetupListeners();
        this._activeThumb = this._thumb1;
        this.SetThumbValue(defaultMin ?? min);
        this._activeThumb = this._thumb2;
        this.SetThumbValue(defaultMax ?? max);
        this._activeThumb = null;
    }

    SetupListeners() {
        this._thumb1.addEventListener("mousedown", (event) => { this.isSliding = true; this._activeThumb = this._thumb1; });
        this._thumb2.addEventListener("mousedown", (event) => { this.isSliding = true; this._activeThumb = this._thumb2; });
        document.addEventListener("mouseup", event => {
            if (this.isSliding) {
                event.preventDefault();
                event.stopPropagation();
                this._activeThumb = null;
                this.isSliding = false;
            }
        });
        document.addEventListener("mousemove", (event) => {
            if (this._activeThumb === null || !this.isSliding) {
                return;
            }
            event.preventDefault();
            let pos = event.clientX - this._bar.getBoundingClientRect().left;
            if (pos < 0) {
                pos = this._bar.getBoundingClientRect().left;
            }
            if (pos > this._bar.getBoundingClientRect().right) {
                pos = this._bar.getBoundingClientRect().right;
            }
            pos -= this.thumbHalfWidth;
            this.SetThumbValue(pos / this._ratio);
        });
        this._container.addEventListener("click", (event) => {
            event.preventDefault();
            event.stopPropagation();
            if (this.clicked === true) {
                return;
            }
            this.clicked = true;
            let pos = event.clientX - this._bar.getBoundingClientRect().left;
            if (parseFloat(this._thumb2.dataset.curPos) <= pos) {
                this._activeThumb = this._thumb2;
            } else if (pos <= parseFloat(this._thumb1.dataset.curPos)) {
                this._activeThumb = this._thumb1;
            } else {
                if (pos - parseFloat(this._thumb1.dataset.curPos) > parseFloat(this._thumb2.dataset.curPos) - pos) {
                    this._activeThumb = this._thumb2;
                } else {
                    this._activeThumb = this._thumb1;
                }
            }
            this._activeThumb.classList.add("active");
            this.SetThumbValue(pos / this._ratio);
            setTimeout(() => {
                this.clicked = false;
                this._activeThumb.classList.remove("active");
                this._activeThumb = null;
            }, 500);
        });
    }

    SetThumbValue(value) {
        if (this._activeThumb.dataset.isMinSlider === "true") {
            if (value >= this.maxValue) {
                value = this.maxValue - 1;
            }
            value = parseInt(value);
            this.minValue = value;
        } else {
            if (value <= this.minValue) {
                value = this.minValue + 1;
            }
            value = parseInt(value);
            this.maxValue = value;
        }
        if (value <= this._rangeMin) {
            value = this._rangeMin;
        }
        if (value >= this._rangeMax) {
            value = this._rangeMax;
        }
        value *= this._ratio;
        this.MoveThumbX(value);
    }

    MoveThumbX(xc) {
        if (this._activeThumb === null) {
            return null;
        }
        xc -= this.thumbHalfWidth;
        this.events.dispatchEvent(new Event("change"));
        this._activeThumb.style.left = xc + "px";
        this._activeThumb.dataset.curPos = xc;
    }
}