<template>
	<div>
		<input type="text" class="flatpickr">
	</div>
</template>

<script>
	import flatpickr from 'flatpickr';
	import 'flatpickr/dist/flatpickr.css';


	export default {
		props: {
			mode: {
				type: String,
				default: 'single',
			},
			dateFormat: {
				type: String,
				default: 'Y-m-d',
			},
			conjunction: {
				type: String,
				default: " :: ",
			},
			rangeSeparatorString: {
				type: String,
				default: ' ~ ',
			},
			allowInput: {
				type: Boolean,
				default: true,
			},
			defaultDate: {

			}
		},
		data () {
			return {
				date: null,
			}
		},
		components: {
			// flatPickr
		},
		mounted: function() {
			let here = this;
			let element = flatpickr('.flatpickr', {
				onReady: function() {
				},
				onOpen: function(selectedDates, dateStr, instance) {
					// EE has a bind on the WHOLE DOCUMENT
					// to remove .open classes on click.
					// This removes the .open class that flatpickr adds
					// to its .flatpickr-calendar element
					// We're mimicking the .open class with something custom.
					// Ugh.
					instance.calendarContainer.className += ' flatpickr-opened';
				},
				onChange: function(selectedDates, dateStr, instance) {
					here.$emit('input', dateStr);
				},
				onClose: function(selectedDates, dateStr, instance) {
					instance.calendarContainer.className = instance.calendarContainer.className.replace(' flatpickr-opened', '');
				},
				mode: this.mode,
				dateFormat: this.dateFormat,
				conjunction: this.conjunction,
				allowInput: this.allowInput,
				defaultDate: this.defaultDate,
				locale: {
					rangeSeparator: this.rangeSeparatorString
				}
			});
		}
	}
</script>

<style>
	.flatpickr-opened {
		display: inline-block;
		z-index: 99999;
		opacity: 1;
		max-height: 640px;
		visibility: visible;
	}
</style>