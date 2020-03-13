<?php

namespace lib;

class Validator
{
    /**
     * @param array $data - list with the values to be validated
     * @param array $rules - the rules to be applied to each list element
     *      The rules should be separated by |
     *      Example:
     *           $rules = [
     *              'email' => 'required|email',
     *              'age' => 'required|numeric',
     *              'expire_date' => 'date|after:tomorrow',
     *              'punctuation' => 'digits_between:1,10|different:4,5',
     *              'color' => 'string|in:red,green,blue',
     *              'phone' => 'size:8'
     *           ];
     *      Options:
     *          - required - field must exist in $data
     *          - email - field must be in mail format
     *          - numeric - field must be a number
     *          - accepted - The field under validation must be yes, on, 1, or true
     *          - after:date - The field under validation must be a value after a given date.
     *                    The dates will be passed into the strtotime PHP function
     *          - after_or_equal:date - Similar to after, but considering equal
     *          - before:date - Similar to previous 2 arguments
     *          - alpha - The field under validation must be entirely alphabetic characters.
     *          - alpha_numeric - The field under validation may have alpha-numeric characters
     *          - between:min,max - The field under validation must have a size between the given min
     *                    and max (equal not included). Strings and arrays are evaluated based in size
     *          - boolean - The field under validation must be able to be cast as a boolean.
     *                      Accepted input are true, false, 1, 0, "1", and "0".
     *          - date - The field under validation must be a valid date according to
     *                   the strtotime PHP function
     *          - date_equals:date - The field under validation must be equal to the given date
     *                              The dates will be passed into the PHP strtotime function.
     *          - date_format:format - The field under validation must match the given format,
     *                                 function \DateTime::createFromFormat is used
     *          - different:field1,field2 - The field under validation must have a different
     *                                      value than given fields.
     *          - digits:value - The field under validation must be numeric and must have an exact length of value.
     *          - digits_between:min,max - The field under validation must have a length between
     *                              the given min and max (equal not included).
     *          - in:foo,bar - The field under validation must be included in the given list of values
     *          - not_in:foo,bar - The field under validation must not be included in the given list of values
     *          - integer - The field under validation must be an integer
     *          - string - The field under validation must be a string
     *          - max:value -  must be less than or equal a maximum value.
     *                      Strings and arrays are evaluated based in size
     *          - min:value -  must be higher than or equal a minimum value.
     *                      Strings and arrays are evaluated based in size
     *          - regex:pattern - The field under validation must match the given regular expression.
     *          - size:value - The field under validation must have a size matching the given value,
     *                    represented by the number of chars if integer or string and the count function for arrays
     * @return array $result - return if the data is valid, if negative case returns a message explaining why is invalid
     */
    public function validate($data, $rules)
    {
        foreach ($rules as $field => $field_rules) {
            // Set as null if field does not exist in data array
            if (isset($data[$field])) {
                $value = $data[$field];
            } else {
                $value = null;
            }
            
            $separeted_rules = explode("|", $field_rules);
            foreach ($separeted_rules as $rule) {
                $result = $this->validateField($field, $value, $rule);
                if (!$result['valid']) {
                    return $result;
                }
            }
        }
        // If code reached here, all $results are valid, so return the last one
        return $result;
    }

    public function validateField($field, $value, $rule)
    {
        $to_return = true;

        if ($rule == 'email') {
            $to_return = $this->validateEmail($value);
        } elseif ($rule == 'required') {
            $to_return = $this->validateRequired($value, $field);
        } elseif ($rule == 'numeric') {
            $to_return = $this->validateNumeric($value, $field);
        } elseif ($rule == 'accepted') {
            $to_return = $this->validateAccepted($value, $field);
        } elseif ($rule == 'alpha') {
            $to_return = $this->validateAlphabetic($value, $field);
        } elseif ($rule == 'alpha_numeric') {
            $to_return = $this->validateAlphaNumeric($value, $field);
        } elseif ($rule == 'array') {
            $to_return = $this->validateArray($value, $field);
        } elseif ($rule == 'boolean') {
            $to_return = $this->validateBoolean($value, $field);
        } elseif ($rule == 'integer') {
            $to_return = $this->validateInteger($value, $field);
        } elseif ($rule == 'string') {
            $to_return = $this->validateString($value, $field);
        } elseif ($rule == 'date') {
            $to_return = $this->validateDate($value, $field);
        } elseif (strpos($rule, 'different') !== false) {
            $to_return = $this->validateDifferent($rule, $value, $field);
        } elseif (strpos($rule, 'size') !== false) {
            $to_return = $this->validateSize($rule, $value, $field);
        } elseif (strpos($rule, 'regex') !== false) {
            $to_return = $this->validateRegex($rule, $value, $field);
        } elseif (strpos($rule, 'date_equals') !== false) {
            $to_return = $this->validateDateEquals($rule, $value, $field);
        } elseif (strpos($rule, 'date_format') !== false) {
            $to_return = $this->validateDateFormat($rule, $value, $field);
        } elseif (strpos($rule, 'after_or_equal') !== false) {
            $to_return = $this->validateAfterOrEqualDate($rule, $value, $field);
        } elseif (strpos($rule, 'after') !== false) {
            $to_return = $this->validateAfterDate($rule, $value, $field);
        } elseif (strpos($rule, 'before_or_equal') !== false) {
            $to_return = $this->validateBeforeOrEqualDate($rule, $value, $field);
        } elseif (strpos($rule, 'before') !== false) {
            $to_return = $this->validateBeforeDate($rule, $value, $field);
        } elseif (strpos($rule, 'max') !== false) {
            $to_return = $this->validateMax($rule, $value, $field);
        } elseif (strpos($rule, 'min') !== false) {
            $to_return = $this->validateMin($rule, $value, $field);
        } elseif (strpos($rule, 'digits_between') !== false) {
            $to_return = $this->validateDigitsBetween($rule, $value, $field);
        } elseif (strpos($rule, 'between') !== false) {
            $to_return = $this->validateBetween($rule, $value, $field);
        } elseif (strpos($rule, 'digits') !== false) {
            $to_return = $this->validateDigits($rule, $value, $field);
        } elseif (strpos($rule, 'not_in') !== false) {
            $to_return = $this->validateNotIn($rule, $value, $field);
        } elseif (strpos($rule, 'in') !== false) {
            $to_return = $this->validateIn($rule, $value, $field);
        }

        if ($to_return === true) {
            return array("valid" => true, "invalid_message" => "");
        } else {
            return $to_return;
        }
    }

    public function validateRegex($rule, $value, $field)
    {
        $pattern = explode(":", $rule)[1];
        if (!preg_match($pattern, $value)) {
            $errMessage = "Field " . $field . " does not match pattern";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateDifferent($rule, $value, $field)
    {
        $fields = explode(":", $rule)[1];
        $fields = explode(",", $fields);
        foreach ($fields as $field) {
            if ($field == $value) {
                $errMessage = "Field " . $field . " not different from specified fields";
                return array("valid" => false, "invalid_message" => $errMessage);
            }
        }
        return true;
    }

    public function validateSize($rule, $value, $field)
    {
        $size = explode(":", $rule)[1];
        if (is_array($value)) {
            $valid = (count($value) == $size);
        } else {
            $valid = (strlen((string)$value) == $size);
        }

        if (!$valid) {
            $errMessage = "Field " . $field . " must have size " . $size;
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }
    
    public function validateDigits($rule, $value, $field)
    {
        $size = explode(":", $rule)[1];
        if (!(is_numeric($value) && strlen((string)$value) == $size)) {
            $errMessage = "Field " . $field . " must contain only numbers and must have size " . $size;
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateMax($rule, $value, $field)
    {
        $max = explode(":", $rule)[1];
        if (!($value <= $max)) {
            $errMessage = "Field " . $field . " exceeds maximum allowed value";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateMin($rule, $value, $field)
    {
        $min = explode(":", $rule)[1];
        if (!($value >= $min)) {
            $errMessage = "Field " . $field . " smaller than minimum allowed value";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateDigitsBetween($rule, $value, $field)
    {
        $min_max = explode(":", $rule)[1];
        $split = explode(",", $min_max);
        $min = $split[0];
        $max = $split[1];
        $length = strlen((string)$value);

        if (!($length > $min && $length < $max)) {
            $errMessage = "Field " . $field . " length not between given values";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateBetween($rule, $value, $field)
    {
        $min_max = explode(":", $rule)[1];
        $split = explode(",", $min_max);
        $min = $split[0];
        $max = $split[1];

        if (!($value > $min && $value < $max)) {
            $errMessage = "Field " . $field . " not between given values";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateBoolean($value, $field)
    {
        $valid = ($value === true || $value === false || $value === 1
        || $value === 0 || $value === '1' || $value === '0');
        if (!$valid) {
            $errMessage = "Field " . $field . " must be a boolean";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateInteger($value, $field)
    {
        if (!is_int($value)) {
            $errMessage = "Field " . $field . " must be an integer";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateString($value, $field)
    {
        if (!is_string($value)) {
            $errMessage = "Field " . $field . " must be a string";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateAccepted($value, $field)
    {
        if (!($value == 'yes' || $value == 'on' || $value == 1 || $value === true)) {
            $errMessage = "Field " . $field . " must be yes, on, 1 or true";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateNumeric($value, $field)
    {
        if (!(is_numeric($value))) {
            $errMessage = "Field " . $field . " must be numeric";
            return array("valid" => false, "invalid_message" => $errMessage);
       }
       return true;
    }

    public function validateEmail($value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errMessage = "Invalid email format";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateRequired($value, $field)
    {
        if (!$value) {
            $errMessage = "Field " . $field . " is required";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateDate($value, $field)
    {
        if (!strtotime($value)) {
            $errMessage = "Field " . $field . " must be a date";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateDateFormat($rule, $value, $field)
    {
        $format = explode(":", $rule)[1];
        if (!(\DateTime::createFromFormat($format, $value))) {
            $errMessage = "Field " . $field . " does not match given date format";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateArray($value, $field)
    {
        if (!is_array($value)) {
            $errMessage = "Field " . $field . " must be an array";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateAlphaNumeric($value, $field)
    {
        if (!ctype_alnum($value)) {
            $errMessage = "Field " . $field . " must contain only alphanumerical characters";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateAlphabetic($value, $field)
    {
        if (!ctype_alpha($value)) {
            $errMessage = "Field " . $field . " must contain only alphabetical characters";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateNotIn($rule, $value, $field)
    {
        $not_allowed_values = explode(":", $rule)[1];
        $not_allowed_values = explode(",", $not_allowed_values);

        if (in_array($value, $not_allowed_values)) {
            $errMessage = "Field " . $field . " value not accepted";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateIn($rule, $value, $field)
    {
        $accepted_values = explode(":", $rule)[1];
        $accepted_values = explode(",", $accepted_values);

        if (!in_array($value, $accepted_values)) {
            $errMessage = "Field " . $field . " value not accepted";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateDateEquals($rule, $value, $field)
    {
        $given_date = explode(":", $rule)[1];
        $value = strtotime($value);
        $given_date = strtotime($given_date);

        if (!($value && $given_date && $value == $given_date)) {
            $errMessage = "Field " . $field . " value not equal given date";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateAfterDate($rule, $value, $field)
    {
        $given_date = explode(":", $rule)[1];
        $given_date = strtotime($given_date);
        $value = strtotime($value);

        if (!($value && $given_date && $value > $given_date)) {
            $errMessage = "Field " . $field . " value not after given date";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateAfterOrEqualDate($rule, $value, $field)
    {
        $given_date = explode(":", $rule)[1];
        $given_date = strtotime($given_date);
        $value = strtotime($value);
        if (! ($value && $given_date && $value >= $given_date)) {
            $errMessage = "Field " . $field . " value not after or equal given date";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }
    

    public function validateBeforeDate($rule, $value, $field)
    {
        $given_date = explode(":", $rule)[1];
        $given_date = strtotime($given_date);
        $value = strtotime($value);

        if (!($value && $given_date && $value < $given_date)) {
            $errMessage = "Field " . $field . " value not before given date";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }

    public function validateBeforeOrEqualDate($rule, $value, $field)
    {
        $given_date = explode(":", $rule)[1];
        $given_date = strtotime($given_date);
        $value = strtotime($value);

        if (!($value && $given_date && $value <= $given_date)) {
            $errMessage = "Field " . $field . " value not before or equal given date";
            return array("valid" => false, "invalid_message" => $errMessage);
        }
        return true;
    }
}
