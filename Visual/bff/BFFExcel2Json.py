#---------------------------------------------------------------------------
# Copyright 2014 The Open Source Electronic Health Record Agent
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#     http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
#---------------------------------------------------------------------------

import xlrd
from xlrd import open_workbook,cellname,xldate_as_tuple
from datetime import datetime, date, time
import csv
import logging
import json

def test_sheet(test_xls):
  book = open_workbook(test_xls)
  sheet0 = book.sheet_by_index(0)
  for rx in range(sheet0.nrows):
    for col_index in range(sheet0.ncols):
      print sheet0.cell(rx, col_index)
  # print sheet0.row(0)
  # print sheet0.col(0)
  # print
  # print sheet0.row_slice(0,1)
  # print sheet0.row_slice(0,1,2)
  # print sheet0.row_values(0,1)
  # print sheet0.row_values(0,1,2)
  # print sheet0.row_types(0,1)
  # print sheet0.row_types(0,1,2)

typeDict = {
  xlrd.XL_CELL_NUMBER: "Number",
  xlrd.XL_CELL_TEXT: "Text",
  xlrd.XL_CELL_DATE: "Date",
  xlrd.XL_CELL_BLANK: "Blank",
  xlrd.XL_CELL_EMPTY: "Empty",
  xlrd.XL_CELL_ERROR: "Error",
  xlrd.XL_CELL_BOOLEAN: "Boolean",
}

def convertToInt(value):
  try:
    return int(value)
  except ValueError as ve:
    return value

def convertToDate(value):
  date_value = xldate_as_tuple(value, 0)
  return datetime(*date_value).strftime("%Y-%m-%d")

def convertToBool(value):
  if value:
    return "TRUE"
  return "FALSE"

typeDictConvert = {
  xlrd.XL_CELL_NUMBER: convertToInt,
  xlrd.XL_CELL_TEXT: None,
  xlrd.XL_CELL_DATE: convertToDate,
  xlrd.XL_CELL_BLANK: None,
  xlrd.XL_CELL_EMPTY: None,
  xlrd.XL_CELL_ERROR: None,
  xlrd.XL_CELL_BOOLEAN: convertToBool,
}

# convert the excel name fields to standard json output name
bffFieldsConvert = {
  "Business Function Name" : 'name',
  "Parent Business Function" : 'parent',
  "BFF Number": 'number',
  "Function Description": 'description'
}

def bffFieldsConvertFunc(x):
  if x in bffFieldsConvert:
    return bffFieldsConvert[x]
  return x

def convertBFFExcelToJson(input, output):
  book = open_workbook(input)
  sheet = book.sheet_by_index(0)
  data_row = 0
  fields = None
  all_bff_nodes = dict(); # all the nodes
  isHeader = True
  rootNode = ''
  for row_index in xrange(sheet.nrows):
    isHeader = True
    row_types = sheet.row_types(row_index)
    assert len(row_types) == sheet.ncols
    """ Try to identify the header of file by checking first row with non-empty type """
    for idx,row_type in enumerate(row_types):
      if row_type == xlrd.XL_CELL_BLANK or row_type == xlrd.XL_CELL_EMPTY:
        isHeader = False
        break
    if isHeader:
      data_row = row_index + 1
      fields = sheet.row_values(row_index)
      fields = map(bffFieldsConvertFunc, fields)
      print fields
      break
  if not isHeader:
    logging.error("No Valid Header From input file")
    return
  # Read rest of the BFF data from data_row
  for row_index in xrange(data_row, sheet.nrows):
    curNode = dict()
    for col_index in xrange(sheet.ncols):
      cell = sheet.cell(row_index, col_index)
      cType = cell.ctype
      if cType == xlrd.XL_CELL_BLANK or cType == xlrd.XL_CELL_EMPTY:
        continue
      convFunc = typeDictConvert.get(cell.ctype)
      cValue = cell.value
      if convFunc: 
        cValue = convFunc(cValue)
      if not cValue:
        continue
      curNode[fields[col_index]] = cValue
    # print curNode
    all_bff_nodes[curNode[fields[0]]] = curNode
    # add to the right parent node
    if 'parent' in curNode:
      parentName = curNode['parent']
      if parentName in all_bff_nodes:
        parentNode = all_bff_nodes[parentName]
        logging.error('adding child: %s to %s' % (curNode['name'], parentName))
        parentNode.setdefault('children',[]).append(curNode)
      else:
        logging.error('could not identify parent name: (%s)' % parentName)
    else:
      rootNode = curNode

  with open(output, "w") as outputJson:
    json.dump(rootNode, outputJson)
  # with open(output, "wb") as outCsv:
  #   csvWrt = csv.writer(outCsv, lineterminator="\n")
  #   csvWrt.writerow(fields)
  #   for row_index in xrange(data_row, sheet.nrows):
  #     curRow = []
  #     for col_index in xrange(sheet.ncols):
  #       cell = sheet.cell(row_index, col_index)
  #       name = cellname(row_index, col_index)
  #       cType = typeDict.get(cell.ctype, "Unknown")
  #       convFunc = typeDictConvert.get(cell.ctype)
  #       cValue = cell.value
  #       if convFunc:
  #         cValue = convFunc(cValue)
  #       curRow.append(cValue)
  #     """ get rid of rows just with ID field """
  #     if int(curRow[0]) and curRow[1:] == [''] * (len(curRow)-1):
  #       logging.debug("Ignore empty row %s " % curRow)
  #       continue
  #     csvWrt.writerow(curRow)
  # with open(output, 'rb') as csvFile:
  #   csvReader = csv.reader(csvFile)
  #   for row in csvReader:
  #     logging.debug(','.join(row))

def main():
  import argparse
  parser = argparse.ArgumentParser("Convert BFF Excel SpreadSheet to JSON")
  parser.add_argument('input', help='input BFF excel spreadsheet')
  parser.add_argument('output', help='output JSON file')
  result = parser.parse_args()
  print (result)
  # test_sheet(result.input);
  convertBFFExcelToJson(result.input, result.output)

if __name__ == '__main__':
  main()
