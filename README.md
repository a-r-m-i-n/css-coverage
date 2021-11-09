# EXT:css_coverage

TYPO3 CMS extension which checks used CSS selectors in HTML output of the current page
and removes CSS declarations which are unused. 

This increases the CSS code coverage dramatically and reduces the overall page load. Perfectly suited for one pagers! 

**Experimental** 


## Installation

Include the TypoScript shipped in EXT:css_coverage in your TypoScript template.

This is the default setup:
```
plugin.tx_csscoverage {
	enabled = 1
	debug = 1
	excluded {
		# Exclude any files you want, like here external resources
		1 = http://*
		2 = https://*
	}
	selectorWildcards {
		# Here define selectors, set dynamically by javascript
		1 = .show
		2 = .fade
		10 = .popover*
	}
}
```

When debug is enabled, it displays in HTML comments like this:

```
<!-- Saved 2.2KB in /typo3temp/assets/css/8015c8c4ac.css -->
<!-- Saved 84.4KB in /assets/style.css -->
```
