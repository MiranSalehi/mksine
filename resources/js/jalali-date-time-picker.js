/**
 * Alpine component behind `DateTimePicker::jalali()`, rendered by core's own Blade view so it does not
 * depend on the markup or JavaScript of Filament's picker. `state` stays a Gregorian `YYYY-MM-DD HH:mm:ss` string.
 *
 * Derived from Filament's date-time-picker.js (MIT, Copyright (c) Filament Group) and
 * mokhosh/filament-jalali (MIT, Copyright (c) mokhosh <mskhoshnazar@gmail.com>).
 * The calendar arithmetic mirrors `Miran\Mksine\Support\JalaliCalendar`.
 */

const EPOCH_JULIAN_DAY = 1948320
const UNIX_EPOCH_JULIAN_DAY = 2440588
const MILLISECONDS_PER_DAY = 86400000

const daysBeforeYear = (year) =>
    365 * (year - 1) + Math.floor((8 * year + 21) / 33)

const daysBeforeMonth = (month) => (month <= 6 ? 31 * month : 30 * month + 6)

const isLeapYear = (year) => (((25 * year + 11) % 33) + 33) % 33 < 8

const daysInMonth = (year, month) => {
    if (month <= 5) {
        return 31
    }

    if (month <= 10) {
        return 30
    }

    return isLeapYear(year) ? 30 : 29
}

const pad = (value, length = 2) => String(value).padStart(length, '0')

class JalaliDate {
    constructor(julianDay, hours = 0, minutes = 0, seconds = 0) {
        const daysSinceEpoch = julianDay - EPOCH_JULIAN_DAY
        const year = 1 + Math.floor((33 * daysSinceEpoch + 3) / 12053)
        const dayOfYear = daysSinceEpoch - daysBeforeYear(year)
        const month =
            dayOfYear < 186
                ? Math.floor(dayOfYear / 31)
                : Math.floor((dayOfYear - 6) / 30)

        this.julianDay = julianDay
        this.hours = hours
        this.minutes = minutes
        this.seconds = seconds
        this.jalaliYear = year
        this.jalaliMonth = month
        this.jalaliDay = dayOfYear - daysBeforeMonth(month) + 1
    }

    static fromJalali(year, month, day, hours = 0, minutes = 0, seconds = 0) {
        return new JalaliDate(
            EPOCH_JULIAN_DAY +
                daysBeforeYear(year) +
                daysBeforeMonth(month) +
                day -
                1,
            hours,
            minutes,
            seconds,
        )
    }

    static fromGregorian(year, month, day, hours = 0, minutes = 0, seconds = 0) {
        return new JalaliDate(
            Date.UTC(year, month - 1, day) / MILLISECONDS_PER_DAY +
                UNIX_EPOCH_JULIAN_DAY,
            hours,
            minutes,
            seconds,
        )
    }

    static fromNativeDate(date) {
        return JalaliDate.fromGregorian(
            date.getFullYear(),
            date.getMonth() + 1,
            date.getDate(),
            date.getHours(),
            date.getMinutes(),
            date.getSeconds(),
        )
    }

    static now() {
        return JalaliDate.fromNativeDate(new Date())
    }

    static parse(value) {
        if (value === null || value === undefined || value === '') {
            return null
        }

        const parts = String(value).match(
            /^(\d{4})-(\d{1,2})-(\d{1,2})(?:[ T](\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?$/,
        )

        if (parts) {
            return JalaliDate.fromGregorian(
                ...parts.slice(1).map((part) => Number(part ?? 0)),
            )
        }

        const date = new Date(value)

        return Number.isNaN(date.getTime())
            ? null
            : JalaliDate.fromNativeDate(date)
    }

    date() {
        return this.jalaliDay
    }

    month() {
        return this.jalaliMonth
    }

    year() {
        return this.jalaliYear
    }

    weekday() {
        return (this.julianDay + 1) % 7
    }

    daysInMonth() {
        return daysInMonth(this.jalaliYear, this.jalaliMonth)
    }

    withDate(day) {
        return JalaliDate.fromJalali(
            this.jalaliYear,
            this.jalaliMonth,
            day,
            this.hours,
            this.minutes,
            this.seconds,
        )
    }

    withMonth(month) {
        return JalaliDate.fromJalali(
            this.jalaliYear,
            month,
            Math.min(this.jalaliDay, daysInMonth(this.jalaliYear, month)),
            this.hours,
            this.minutes,
            this.seconds,
        )
    }

    withYear(year) {
        return JalaliDate.fromJalali(
            year,
            this.jalaliMonth,
            Math.min(this.jalaliDay, daysInMonth(year, this.jalaliMonth)),
            this.hours,
            this.minutes,
            this.seconds,
        )
    }

    withTime(hours, minutes, seconds) {
        return new JalaliDate(this.julianDay, hours, minutes, seconds)
    }

    addDays(days) {
        return new JalaliDate(
            this.julianDay + days,
            this.hours,
            this.minutes,
            this.seconds,
        )
    }

    secondsSinceEpoch() {
        return (
            this.julianDay * 86400 +
            this.hours * 3600 +
            this.minutes * 60 +
            this.seconds
        )
    }

    isAfter(date) {
        return this.secondsSinceEpoch() > date.secondsSinceEpoch()
    }

    isBefore(date) {
        return this.secondsSinceEpoch() < date.secondsSinceEpoch()
    }

    toGregorianString() {
        const date = new Date(
            (this.julianDay - UNIX_EPOCH_JULIAN_DAY) * MILLISECONDS_PER_DAY,
        )

        return (
            `${pad(date.getUTCFullYear(), 4)}-${pad(date.getUTCMonth() + 1)}-${pad(date.getUTCDate())} ` +
            `${pad(this.hours)}:${pad(this.minutes)}:${pad(this.seconds)}`
        )
    }

    /**
     * Formats with PHP date characters, like `JalaliCalendar::format()`.
     */
    format(format, { months, days, daysShort }) {
        const tokens = {
            Y: () => this.jalaliYear,
            y: () => pad(this.jalaliYear % 100),
            m: () => pad(this.jalaliMonth + 1),
            n: () => this.jalaliMonth + 1,
            d: () => pad(this.jalaliDay),
            j: () => this.jalaliDay,
            t: () => this.daysInMonth(),
            L: () => (isLeapYear(this.jalaliYear) ? 1 : 0),
            F: () => months[this.jalaliMonth],
            M: () => [...months[this.jalaliMonth]].slice(0, 3).join(''),
            l: () => days[this.weekday()],
            D: () => daysShort[this.weekday()],
            H: () => pad(this.hours),
            G: () => this.hours,
            h: () => pad(this.hours % 12 || 12),
            g: () => this.hours % 12 || 12,
            i: () => pad(this.minutes),
            s: () => pad(this.seconds),
            A: () => (this.hours < 12 ? 'AM' : 'PM'),
            a: () => (this.hours < 12 ? 'am' : 'pm'),
        }

        return format.replace(/\\(.)|./gsu, (match, escaped) => {
            if (escaped !== undefined) {
                return escaped
            }

            return tokens[match]?.() ?? match
        })
    }
}

export default function mksJalaliDateTimePicker({
    calendar,
    defaultFocusedDate,
    firstDayOfWeek,
    isAutofocused,
    isInteractive,
    shouldCloseOnDateSelection,
    state,
}) {
    const panelGap = 8

    return {
        daysInFocusedMonth: [],

        displayText: '',

        emptyDaysInFocusedMonth: [],

        focusedDate: null,

        focusedMonth: null,

        focusedYear: null,

        hasValidationMessage: false,

        hour: null,

        isClearingState: false,

        isPanelOpen: false,

        stopTrackingPanelPosition: null,

        minute: null,

        second: null,

        state,

        defaultFocusedDate,

        dayLabels: [],

        months: [],

        init() {
            this.$nextTick(() => {
                this.focusedDate ??=
                    this.getDefaultFocusedDate() ?? JalaliDate.now()
                this.focusedMonth ??= this.focusedDate.month()
                this.focusedYear ??= this.focusedDate.year()
            })

            let date =
                this.getSelectedDate() ??
                this.getDefaultFocusedDate() ??
                JalaliDate.now().withTime(0, 0, 0)

            if (this.getMaxDate() !== null && date.isAfter(this.getMaxDate())) {
                date = null
            } else if (
                this.getMinDate() !== null &&
                date.isBefore(this.getMinDate())
            ) {
                date = null
            }

            this.hour = date?.hours ?? 0
            this.minute = date?.minutes ?? 0
            this.second = date?.seconds ?? 0

            this.setDisplayText()
            this.months = calendar.months
            this.dayLabels = this.getDayLabels()

            if (isAutofocused) {
                this.$nextTick(() =>
                    this.togglePanelVisibility(this.$refs.button),
                )
            }

            this.$watch('focusedMonth', () => {
                this.focusedMonth = +this.focusedMonth

                if (this.focusedDate.month() === this.focusedMonth) {
                    return
                }

                this.focusedDate = this.focusedDate.withMonth(this.focusedMonth)
            })

            this.$watch('focusedYear', () => {
                if (this.focusedYear?.length > 4) {
                    this.focusedYear = this.focusedYear.substring(0, 4)
                }

                if (!this.focusedYear || this.focusedYear?.length !== 4) {
                    return
                }

                let year = +this.focusedYear

                if (!Number.isInteger(year)) {
                    year = JalaliDate.now().year()

                    this.focusedYear = year
                }

                if (this.focusedDate.year() === year) {
                    return
                }

                this.focusedDate = this.focusedDate.withYear(year)
            })

            this.$watch('focusedDate', () => {
                let month = this.focusedDate.month()
                let year = this.focusedDate.year()

                if (this.focusedMonth !== month) {
                    this.focusedMonth = month
                }

                if (this.focusedYear !== year) {
                    this.focusedYear = year
                }

                this.setupDaysGrid()
            })

            this.$watch('hour', () => {
                let hour = +this.hour

                if (!Number.isInteger(hour)) {
                    this.hour = 0
                } else if (hour > 23) {
                    this.hour = 0
                } else if (hour < 0) {
                    this.hour = 23
                } else {
                    this.hour = hour
                }

                if (this.isClearingState) {
                    return
                }

                this.setState(this.getSelectedDate() ?? this.focusedDate)
            })

            this.$watch('minute', () => {
                let minute = +this.minute

                if (!Number.isInteger(minute)) {
                    this.minute = 0
                } else if (minute > 59) {
                    this.minute = 0
                } else if (minute < 0) {
                    this.minute = 59
                } else {
                    this.minute = minute
                }

                if (this.isClearingState) {
                    return
                }

                this.setState(this.getSelectedDate() ?? this.focusedDate)
            })

            this.$watch('second', () => {
                let second = +this.second

                if (!Number.isInteger(second)) {
                    this.second = 0
                } else if (second > 59) {
                    this.second = 0
                } else if (second < 0) {
                    this.second = 59
                } else {
                    this.second = second
                }

                if (this.isClearingState) {
                    return
                }

                this.setState(this.getSelectedDate() ?? this.focusedDate)
            })

            this.$watch('state', () => {
                if (this.state === undefined) {
                    return
                }

                let date = this.getSelectedDate()

                if (date === null) {
                    this.clearState()

                    return
                }

                if (
                    this.getMaxDate() !== null &&
                    date?.isAfter(this.getMaxDate())
                ) {
                    date = null
                }
                if (
                    this.getMinDate() !== null &&
                    date?.isBefore(this.getMinDate())
                ) {
                    date = null
                }

                const newHour = date?.hours ?? 0
                if (this.hour !== newHour) {
                    this.hour = newHour
                }

                const newMinute = date?.minutes ?? 0
                if (this.minute !== newMinute) {
                    this.minute = newMinute
                }

                const newSecond = date?.seconds ?? 0
                if (this.second !== newSecond) {
                    this.second = newSecond
                }

                this.setDisplayText()
            })
        },

        checkTimeInputValidity(event) {
            const el = event.target
            if (this.isOpen() && !el.validity.valid) {
                el.reportValidity()
            }
        },

        clearState() {
            if (!isInteractive) {
                return
            }

            this.isClearingState = true

            this.setState(null)

            this.hour = 0
            this.minute = 0
            this.second = 0

            this.$nextTick(() => (this.isClearingState = false))
        },

        dateIsDisabled(date) {
            if (
                this.$refs?.disabledDates &&
                JSON.parse(this.$refs.disabledDates.value ?? []).some(
                    (disabledDate) =>
                        JalaliDate.parse(disabledDate)?.julianDay ===
                        date.julianDay,
                )
            ) {
                return true
            }

            if (
                this.getMaxDate() &&
                date.julianDay > this.getMaxDate().julianDay
            ) {
                return true
            }
            if (
                this.getMinDate() &&
                date.julianDay < this.getMinDate().julianDay
            ) {
                return true
            }

            return false
        },

        destroy() {
            this.stopTrackingPanelPosition?.()
        },

        dayIsDisabled(day) {
            this.focusedDate ??= JalaliDate.now()

            return this.dateIsDisabled(this.focusedDate.withDate(day))
        },

        dayIsSelected(day) {
            let selectedDate = this.getSelectedDate()

            if (selectedDate === null) {
                return false
            }

            this.focusedDate ??= JalaliDate.now()

            return (
                selectedDate.date() === day &&
                selectedDate.month() === this.focusedDate.month() &&
                selectedDate.year() === this.focusedDate.year()
            )
        },

        dayIsToday(day) {
            let date = JalaliDate.now()
            this.focusedDate ??= date

            return (
                date.date() === day &&
                date.month() === this.focusedDate.month() &&
                date.year() === this.focusedDate.year()
            )
        },

        isRightToLeft() {
            return getComputedStyle(this.$el).direction === 'rtl'
        },

        moveFocusedDate(days) {
            if (!isInteractive) {
                return
            }

            if (!this.isOpen()) {
                this.togglePanelVisibility()

                return
            }

            this.focusedDate = this.focusedDate.addDays(days)
        },

        focusPreviousDay() {
            this.moveFocusedDate(this.isRightToLeft() ? 1 : -1)
        },

        focusPreviousWeek() {
            this.moveFocusedDate(-7)
        },

        focusNextDay() {
            this.moveFocusedDate(this.isRightToLeft() ? -1 : 1)
        },

        focusNextWeek() {
            this.moveFocusedDate(7)
        },

        getDayLabels() {
            const labels = calendar.daysShort
            const firstDay = firstDayOfWeek % 7

            return [...labels.slice(firstDay), ...labels.slice(0, firstDay)]
        },

        getMaxDate() {
            return JalaliDate.parse(this.$refs.maxDate?.value)
        },

        getMinDate() {
            return JalaliDate.parse(this.$refs.minDate?.value)
        },

        getSelectedDate() {
            return JalaliDate.parse(this.state)
        },

        getDefaultFocusedDate() {
            return JalaliDate.parse(this.defaultFocusedDate)
        },

        togglePanelVisibility() {
            if (!isInteractive) {
                return
            }

            if (this.isOpen()) {
                this.closePanel()

                return
            }

            this.focusedDate =
                this.getSelectedDate() ??
                this.focusedDate ??
                this.getMinDate() ??
                JalaliDate.now()

            this.setupDaysGrid()

            this.isPanelOpen = true

            this.$nextTick(() => {
                this.positionPanel()

                const reposition = () => this.positionPanel()

                window.addEventListener('resize', reposition)
                window.addEventListener('scroll', reposition, true)

                this.stopTrackingPanelPosition = () => {
                    window.removeEventListener('resize', reposition)
                    window.removeEventListener('scroll', reposition, true)
                    this.stopTrackingPanelPosition = null
                }
            })
        },

        closePanel(shouldRestoreFocus = false) {
            if (!this.isOpen()) {
                return
            }

            this.isPanelOpen = false
            this.stopTrackingPanelPosition?.()

            if (shouldRestoreFocus) {
                this.$refs.button.focus()
            }
        },

        positionPanel() {
            const { panel, button } = this.$refs

            if (!this.isOpen() || !panel || !button) {
                return
            }

            panel.style.top = '0px'
            panel.style.left = '0px'

            // A transformed ancestor, such as a modal, moves the origin of `position: fixed`.
            const origin = panel.getBoundingClientRect()
            const trigger = button.getBoundingClientRect()

            const fitsBelow =
                trigger.bottom + panelGap + origin.height <= window.innerHeight
            const fitsAbove = trigger.top - panelGap - origin.height >= 0
            const top =
                fitsBelow || !fitsAbove
                    ? trigger.bottom + panelGap
                    : trigger.top - panelGap - origin.height

            const preferredLeft = this.isRightToLeft()
                ? trigger.right - origin.width
                : trigger.left
            const left = Math.max(
                panelGap,
                Math.min(
                    preferredLeft,
                    window.innerWidth - origin.width - panelGap,
                ),
            )

            panel.style.top = `${top - origin.top}px`
            panel.style.left = `${left - origin.left}px`
        },

        selectDate(day = null) {
            if (day) {
                this.setFocusedDay(day)
            }

            this.focusedDate ??= JalaliDate.now()

            this.setState(this.focusedDate)

            if (shouldCloseOnDateSelection) {
                this.togglePanelVisibility()
            }
        },

        setDisplayText() {
            this.displayText =
                this.getSelectedDate()?.format(
                    calendar.displayFormat,
                    calendar,
                ) ?? ''
        },

        setupDaysGrid() {
            this.focusedDate ??= JalaliDate.now()

            this.emptyDaysInFocusedMonth = Array.from(
                {
                    length:
                        (this.focusedDate.withDate(1).weekday() -
                            (firstDayOfWeek % 7) +
                            7) %
                        7,
                },
                (_, i) => i + 1,
            )

            this.daysInFocusedMonth = Array.from(
                {
                    length: this.focusedDate.daysInMonth(),
                },
                (_, i) => i + 1,
            )
        },

        setFocusedDay(day) {
            this.focusedDate = (this.focusedDate ?? JalaliDate.now()).withDate(
                day,
            )
        },

        setState(date) {
            if (date === null) {
                this.state = null
                this.setDisplayText()

                return
            }

            if (!isInteractive) {
                return
            }

            if (this.dateIsDisabled(date)) {
                return
            }

            this.state = date
                .withTime(
                    +(this.hour ?? 0),
                    +(this.minute ?? 0),
                    +(this.second ?? 0),
                )
                .toGregorianString()

            this.setDisplayText()
        },

        timeInputInvalid(event) {
            const el = event.target

            if (!this.isOpen()) {
                event.preventDefault()
                this.togglePanelVisibility()
            }

            if (!this.hasValidationMessage) {
                this.hasValidationMessage = true

                this.$nextTick(() => {
                    el.reportValidity()
                    this.hasValidationMessage = false
                })
            }
        },

        isOpen() {
            return this.isPanelOpen
        },
    }
}
