# TopProducts Charts

## Request
### Method: POST/JSON
**API URL** /api.php?action=HVTopProducts

Request examples:
```
{
  "date_from": 1645635600000,
  "date_to": 1647795600000,
  "products": ["SC-BL", "HUM-B"],
  "countries": ["US", "CA"],
  "aggregate": false // products is parent
}
```
```
{
  "date_from": 1645635600000,
  "date_to": 1647795600000,
  "products": ["SC", "HUM"],
  "countries": ["US", "CA"],
  "aggregate": true // products is parent
}
```

## Response
### Schema
```
{
   "type":"object",
   "required":["products"],
   "properties":{
        "products":{
            "type":["array"],
            "items":{
               "type":["object"],
                "required":["product_shortname","value"],
                "properties": {
                    "product_shortname":{"type":["string"]},
                    "value":{"type":["number"]}
                }
            }
        }
    }
}
```
### Example
```
{
    "products": [
        {
            "product_shortname": "SC-BL",
            "value": 5570,   
        },
        {
            "product_shortname": "BC",
            "value": 1864,   
        },
    ]
}
```
