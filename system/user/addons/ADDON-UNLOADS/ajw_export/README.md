# AJW Export

Exports data from an SQL query in CSV or XML format.

## Requirements

- ExpressionEngine 2/3/4

## Installation

1. Download the [latest release](https://bitbucket.org/ajweaver/ajw_export/downloads).
2. Copy the `ajw_export` folder to your `system/user/addons` folder (you can ignore the rest of this repository's files).
3. In your ExpressionEngine control panel, visit the Add-On Manager and click Install next to "AJW Export".

## Usage

### `{exp:ajw_export}`

#### Example Usage

The AJW Export is a single tag plugin that can be used to export data from any of your database tables:

```
{exp:ajw_export
	sql="SELECT member_id, screen_name FROM exp_members"
	format="csv"
	delimiter=":"
	filename="output.csv"
}
```

#### Parameters

- `sql`       - the SQL query
- `format`    - CSV or XML (defaults to csv)
- `filename`  - the output filename
- `delimiter` - the delimiter for CSV exports (defaults to comma)
- `root`      - the root node for XML exports (defaults to root)
- `element`   - the repeating element for XML exports (defaults to element)

## Change Log

### 2.0.0

- Initial release for v3

## License

Copyright (C) 2016 Andrew Weaver

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.